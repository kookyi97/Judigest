<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expediente;
use App\Models\Usuario;
use App\Models\Documento;
use App\Models\HistorialExpediente;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\HistorialExpedienteService;

class ExpedienteController extends Controller
{
    protected HistorialExpedienteService $historialService;

    public function __construct(HistorialExpedienteService $historialService)
    {
        $this->historialService = $historialService;
    }

    private function usuarioAutenticado()
    {
        return Auth::user();
    }

    private function puedeAccederExpediente(Expediente $expediente): bool
    {
        $usuario = $this->usuarioAutenticado();

        if (!$usuario) {
            return false;
        }

        // Administrador y secretario pueden acceder a cualquier expediente
        if (in_array($usuario->rol, ['administrador', 'secretario'], true)) {
            return true;
        }

        // El asesor solamente puede acceder a sus expedientes asignados
        if ($usuario->rol === 'asesor') {
            return (int) $expediente->asesor_id === (int) $usuario->id;
        }

        // El practicante solamente puede acceder a sus expedientes asignados
        if ($usuario->rol === 'practicante') {
            return (int) $expediente->practicante_id === (int) $usuario->id;
        }

        return false;
    }

    private function autorizarExpediente(Expediente $expediente): void
    {
        abort_unless(
            $this->puedeAccederExpediente($expediente),
            403,
            'No tiene autorización para acceder a este expediente.'
        );
    }

    private function registrarHistorial(
        Expediente $expediente,
        string $accion,
        string $descripcion,
        ?array $detalles = null
    ): void {
        $this->historialService->registrar(
            $expediente->id,
            $accion,
            $descripcion,
            $detalles ?? []
        );
    }

    public function index()
    {
        $expedientes = Expediente::with([
            'asesor',
            'creador',
            'modificador'
        ])
            ->orderByDesc('created_at')
            ->get();

        $asesores = Usuario::where('rol', 'asesor')
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();

        return Inertia::render('Expedientes/Index', [
            'expedientes' => $expedientes,
            'asesores' => $asesores,
        ]);
    }

    public function create()
    {
        $asesores = Usuario::where('rol', 'asesor')
            ->where('activo', true)
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();

        $practicantes = Usuario::where('rol', 'practicante')
            ->where('activo', true)
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();

        return Inertia::render('Expedientes/Create', [
            'asesores' => $asesores,
            'practicantes' => $practicantes
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente' => 'required|string|max:150',
            'tipo_proceso' => 'required|in:Civil,Penal,Laboral,Familia,Administrativo',
            'asesor_id' => 'required|integer|exists:usuarios,id',
            'practicante_id' => 'nullable|integer|exists:usuarios,id',
            'fecha_ingreso' => 'required|date',
            'descripcion' => 'nullable|string|max:5000',
            'estado' => 'nullable|in:Abierto,En Proceso,Resuelto,Cerrado,Archivado',
        ]);

        $añoActual = date('Y');
        $ultimoExpediente = Expediente::where('numero_expediente', 'like', "EXP-{$añoActual}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimoExpediente) {
            $ultimoNumero = (int) substr($ultimoExpediente->numero_expediente, -4);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }

        $data['numero_expediente'] = sprintf("EXP-%s-%04d", $añoActual, $nuevoNumero);

        abort_unless(
            Usuario::where('id', $data['asesor_id'])
                ->where('rol', 'asesor')
                ->where('activo', true)
                ->exists(),
            422,
            'El asesor seleccionado no es válido.'
        );

        if (!empty($data['practicante_id'])) {
            abort_unless(
                Usuario::where('id', $data['practicante_id'])
                    ->where('rol', 'practicante')
                    ->where('activo', true)
                    ->exists(),
                422,
                'El practicante seleccionado no es válido.'
            );
        }

        $data['creado_por'] = Auth::id();
        $data['estado'] = $data['estado'] ?? 'Abierto';

        DB::transaction(function () use ($data, &$expediente) {
            $expediente = Expediente::create($data);

            $this->registrarHistorial(
                $expediente,
                'CREACION_EXPEDIENTE',
                'Se creó el expediente.',
                [
                    'numero_expediente' => $expediente->numero_expediente,
                ]
            );
        });

        return redirect()
            ->route('expedientes.index')
            ->with('exito', 'Expediente registrado exitosamente.');
    }

    public function edit(Expediente $expediente)
    {
        abort_unless(
            Auth::user()?->rol === 'secretario',
            403,
            'Solo el Secretario puede editar expedientes.'
        );

        $expediente->load([
            'asesor',
            'practicante',
            'creador',
            'modificador',
            'documentos.usuario',
        ]);

        $expediente->documentos->each(function ($documento) {
            $documento->url = route('documentos.ver', $documento->id);
        });

        $asesores = Usuario::where('rol', 'asesor')
            ->where('activo', true)
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();

        return Inertia::render('Expedientes/Edit', [
            'expediente' => $expediente,
            'asesores' => $asesores,
        ]);
    }

    public function update(Request $request, Expediente $expediente)
    {
        abort_unless(
            Auth::user()?->rol === 'secretario',
            403,
            'Solo el Secretario puede editar expedientes.'
        );

        $data = $request->validate([
            'numero_expediente' => 'required|string|max:50|unique:expedientes,numero_expediente,' . $expediente->id,
            'cliente' => 'required|string|max:150',
            'tipo_proceso' => 'required|in:Civil,Penal,Laboral,Familia,Administrativo',
            'asesor_id' => 'required|integer|exists:usuarios,id',
            'practicante_id' => 'nullable|integer|exists:usuarios,id',
            'fecha_ingreso' => 'required|date',
            'estado' => 'required|in:Abierto,En Proceso,Resuelto,Cerrado,Archivado',
            'descripcion' => 'nullable|string|max:5000',
        ], [
            'numero_expediente.required' => 'El número de expediente es obligatorio.',
            'numero_expediente.unique' => 'Este número de expediente ya existe en el sistema.',
            'cliente.required' => 'La información del cliente es obligatoria.',
            'tipo_proceso.required' => 'El tipo de proceso es obligatorio.',
            'asesor_id.required' => 'Debe asignar un asesor responsable.',
            'asesor_id.exists' => 'El asesor seleccionado no es válido.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        abort_unless(
            Usuario::where('id', $data['asesor_id'])
                ->where('rol', 'asesor')
                ->where('activo', true)
                ->exists(),
            422,
            'El asesor seleccionado no es válido.'
        );

        if (!empty($data['practicante_id'])) {
            abort_unless(
                Usuario::where('id', $data['practicante_id'])
                    ->where('rol', 'practicante')
                    ->where('activo', true)
                    ->exists(),
                422,
                'El practicante seleccionado no es válido.'
            );
        }

        $camposModificados = [];

        foreach ($data as $campo => $valor) {
            if ($expediente->getAttribute($campo) != $valor) {
                $camposModificados[] = $campo;
            }
        }

        $data['modificado_por'] = Auth::id();

        DB::transaction(function () use (
            $expediente,
            $data,
            $camposModificados
        ) {
            $expediente->update($data);

            $this->registrarHistorial(
                $expediente,
                'EDICION_EXPEDIENTE',
                'Se actualizaron los datos del expediente.',
                [
                    'campos' => $camposModificados,
                ]
            );
        });

        return redirect()
            ->route('expedientes.index')
            ->with('exito', 'Expediente actualizado exitosamente.');
    }

    public function cambiarEstado(Request $request, Expediente $expediente)
    {
        abort_unless(
            Auth::user()?->rol === 'secretario',
            403,
            'Solo el Secretario puede cambiar el estado.'
        );

        $data = $request->validate([
            'estado' => 'required|in:Abierto,En Proceso,Resuelto,Cerrado,Archivado',
        ]);

        $estadoAnterior = $expediente->estado;

        if ($estadoAnterior === $data['estado']) {
            return back()->with(
                'exito',
                'El expediente ya tiene ese estado.'
            );
        }

        DB::transaction(function () use (
            $expediente,
            $data,
            $estadoAnterior
        ) {
            $expediente->update([
                'estado' => $data['estado'],
                'modificado_por' => Auth::id(),
            ]);

            $this->registrarHistorial(
                $expediente,
                'CAMBIO_ESTADO',
                'Se cambió el estado del expediente.',
                [
                    'estado_anterior' => $estadoAnterior,
                    'nuevo_estado' => $data['estado'],
                ]
            );
        });

        return back()->with(
            'exito',
            'El estado del expediente fue actualizado correctamente.'
        );
    }

    public function archivar(Expediente $expediente)
    {
        abort_unless(
            Auth::user()?->rol === 'secretario',
            403,
            'Solo el Secretario puede archivar expedientes.'
        );

        $estadoAnterior = $expediente->estado;

        if ($estadoAnterior === 'Archivado') {
            return back()->with(
                'exito',
                'El expediente ya se encuentra archivado.'
            );
        }

        DB::transaction(function () use (
            $expediente,
            $estadoAnterior
        ) {
            $expediente->update([
                'estado' => 'Archivado',
                'modificado_por' => Auth::id(),
            ]);

            $this->registrarHistorial(
                $expediente,
                'ARCHIVADO_EXPEDIENTE',
                'Se archivó el expediente.',
                [
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => 'Archivado',
                ]
            );
        });

        return back()->with(
            'exito',
            'El expediente fue archivado correctamente.'
        );
    }

    public function show(Expediente $expediente)
    {
        $this->autorizarExpediente($expediente);

        $expediente->load([
            'asesor',
            'practicante',
            'creador',
            'modificador',
            'documentos.usuario',
        ]);

        return Inertia::render('Expedientes/Show', [
            'expediente' => $expediente,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SUBIR DOCUMENTO
    |--------------------------------------------------------------------------
    */

    public function subirDocumento(
        Request $request,
        Expediente $expediente
    ) {
        $usuario = $this->usuarioAutenticado();

        /*
         * Secretario, asesor y practicante pueden cargar documentos,
         * siempre que tengan acceso al expediente.
         */
        abort_unless(
            $usuario &&
            in_array(
                $usuario->rol,
                ['secretario', 'practicante'],
                true
            ),
            403,
            'No tiene autorización para cargar documentos.'
        );

        /*
         * Verificamos que el usuario tenga acceso al expediente.
         *
         * Secretario:
         *     puede acceder a cualquier expediente.
         *
         * Asesor:
         *     solamente a sus expedientes asignados.
         *
         * Practicante:
         *     solamente a sus expedientes asignados.
         */
        $this->autorizarExpediente($expediente);

        /*
         * Validación del archivo.
         */
        $request->validate([
            'documento' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
            ],
        ], [
            'documento.required' => 'Debe seleccionar un documento.',
            'documento.file' => 'El archivo seleccionado no es válido.',
            'documento.max' => 'El documento no puede superar los 10 MB.',
            'documento.mimes' => 'Tipo de archivo no permitido.',
        ]);

        $archivo = $request->file('documento');

        abort_unless(
            $archivo && $archivo->isValid(),
            422,
            'El archivo no pudo ser validado correctamente.'
        );

        $nombreOriginal = mb_substr(
            $archivo->getClientOriginalName(),
            0,
            255
        );

        $extension = strtolower(
            $archivo->getClientOriginalExtension()
        );

        $nombreArchivo = bin2hex(random_bytes(32)) . '.' . $extension;

        $hash = hash_file(
            'sha256',
            $archivo->getRealPath()
        );

        $directorio = 'documentos/' . $expediente->id;

        $ruta = $directorio . '/' . $nombreArchivo;

        DB::transaction(function () use (
            $archivo,
            $directorio,
            $ruta,
            $nombreArchivo,
            $nombreOriginal,
            $hash,
            $expediente,
            $usuario
        ) {

            /*
             * Guardar archivo.
             */
            $guardado = Storage::disk('local')->putFileAs(
                $directorio,
                $archivo,
                $nombreArchivo
            );

            if (!$guardado) {
                throw new \RuntimeException(
                    'No fue posible almacenar el documento.'
                );
            }

            /*
             * Verificar que realmente exista.
             */
            $rutaCompleta = Storage::disk('local')->path($ruta);

            if (!is_file($rutaCompleta)) {
                throw new \RuntimeException(
                    'El documento no fue almacenado correctamente.'
                );
            }

            /*
             * Verificar integridad mediante SHA-256.
             */
            $hashAlmacenado = hash_file(
                'sha256',
                $rutaCompleta
            );

            if (!hash_equals($hash, $hashAlmacenado)) {
                Storage::disk('local')->delete($ruta);

                throw new \RuntimeException(
                    'No fue posible verificar la integridad del documento.'
                );
            }

            /*
             * Registrar documento en la base de datos.
             */
            $documento = Documento::create([
                'expediente_id' => $expediente->id,
                'nombre_original' => $nombreOriginal,
                'nombre_archivo' => $nombreArchivo,
                'ruta' => $ruta,
                'tipo_mime' => $archivo->getMimeType(),
                'tamano' => $archivo->getSize(),
                'hash_sha256' => $hash,
                'subido_por' => $usuario->id,
            ]);

            /*
             * Registrar la acción en el historial.
             */
            $this->registrarHistorial(
                $expediente,
                'CARGA_DOCUMENTO',
                'Se cargó un documento al expediente.',
                [
                    'documento_id' => $documento->id,
                    'nombre_original' => $nombreOriginal,
                    'tamano' => $archivo->getSize(),
                    'hash_sha256' => $hash,
                    'subido_por' => $usuario->id,
                    'rol_usuario' => $usuario->rol,
                ]
            );
        });

        return back()->with(
            'exito',
            'Documento subido correctamente.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VER DOCUMENTO
    |--------------------------------------------------------------------------
    */

    public function verDocumento(Documento $documento)
    {
        $expediente = $documento->expediente;

        abort_unless(
            $expediente,
            404,
            'El expediente asociado al documento no existe.'
        );

        $this->autorizarExpediente($expediente);

        abort_unless(
            Storage::disk('local')->exists($documento->ruta),
            404,
            'El documento no existe.'
        );

        $rutaCompleta = Storage::disk('local')->path(
            $documento->ruta
        );

        abort_unless(
            is_file($rutaCompleta),
            404,
            'El documento no existe.'
        );

        abort_unless(
            $documento->hash_sha256,
            409,
            'El documento no tiene información de integridad registrada.'
        );

        $hashActual = hash_file(
            'sha256',
            $rutaCompleta
        );

        abort_unless(
            hash_equals(
                $documento->hash_sha256,
                $hashActual
            ),
            409,
            'La integridad del documento no pudo ser verificada.'
        );

        $mime = $documento->tipo_mime
            ?: 'application/octet-stream';

        $tiposVisibles = [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ];

        $disposition = in_array(
            $mime,
            $tiposVisibles,
            true
        )
            ? 'inline'
            : 'attachment';

        return response()->file(
            $rutaCompleta,
            [
                'Content-Type' => $mime,
                'Content-Disposition' =>
                    $disposition .
                    '; filename="' .
                    addslashes($documento->nombre_original) .
                    '"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DESCARGAR DOCUMENTO
    |--------------------------------------------------------------------------
    */

    public function descargarDocumento(Documento $documento)
    {
        $expediente = $documento->expediente;

        abort_unless(
            $expediente,
            404,
            'El expediente asociado al documento no existe.'
        );

        $this->autorizarExpediente($expediente);

        abort_unless(
            Storage::disk('local')->exists($documento->ruta),
            404,
            'El documento no existe.'
        );

        $rutaCompleta = Storage::disk('local')->path(
            $documento->ruta
        );

        abort_unless(
            is_file($rutaCompleta),
            404,
            'El documento no existe.'
        );

        abort_unless(
            $documento->hash_sha256,
            409,
            'No es posible verificar la integridad del documento.'
        );

        $hashActual = hash_file(
            'sha256',
            $rutaCompleta
        );

        abort_unless(
            hash_equals(
                $documento->hash_sha256,
                $hashActual
            ),
            409,
            'La integridad del documento no pudo ser verificada.'
        );

        $this->registrarHistorial(
            $expediente,
            'DESCARGA_DOCUMENTO',
            'Se descargó un documento del expediente.',
            [
                'documento_id' => $documento->id,
                'nombre_original' => $documento->nombre_original,
                'hash_sha256' => $documento->hash_sha256,
            ]
        );

        return response()->download(
            $rutaCompleta,
            $documento->nombre_original,
            [
                'Content-Type' => $documento->tipo_mime
                    ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR DOCUMENTO
    |--------------------------------------------------------------------------
    */

    public function eliminarDocumento(Documento $documento)
    {
        $usuario = $this->usuarioAutenticado();

        /*
         * Solamente el secretario puede eliminar documentos.
         */
        abort_unless(
            $usuario && $usuario->rol === 'secretario',
            403,
            'Solo el Secretario puede eliminar documentos.'
        );

        $expediente = $documento->expediente;

        abort_unless(
            $expediente,
            404,
            'El expediente asociado al documento no existe.'
        );

        $ruta = $documento->ruta;

        abort_unless(
            Storage::disk('local')->exists($ruta),
            404,
            'El documento no existe en el almacenamiento.'
        );

        DB::transaction(function () use (
            $documento,
            $expediente,
            $ruta
        ) {

            $datos = [
                'documento_id' => $documento->id,
                'nombre_original' => $documento->nombre_original,
                'hash_sha256' => $documento->hash_sha256,
            ];

            $eliminado = Storage::disk('local')->delete($ruta);

            if (!$eliminado) {
                throw new \RuntimeException(
                    'No fue posible eliminar el archivo del almacenamiento.'
                );
            }

            $documento->delete();

            $this->registrarHistorial(
                $expediente,
                'ELIMINACION_DOCUMENTO',
                'Se eliminó un documento del expediente.',
                $datos
            );
        });

        return back()->with(
            'exito',
            'Documento eliminado correctamente.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR DOCUMENTOS
    |--------------------------------------------------------------------------
    */

    public function listarDocumentos(Expediente $expediente)
    {
        $usuario = $this->usuarioAutenticado();

        abort_unless(
            $usuario &&
            in_array(
                $usuario->rol,
                ['administrador', 'secretario', 'asesor', 'practicante'],
                true
            ),
            403,
            'No tiene autorización para consultar los documentos.'
        );

        $this->autorizarExpediente($expediente);

        $documentos = Documento::query()
            ->where('expediente_id', $expediente->id)
            ->with([
                'usuario:id,nombre,apellido,rol',
            ])
            ->orderByDesc('created_at')
            ->get([
                'id',
                'expediente_id',
                'nombre_original',
                'tipo_mime',
                'tamano',
                'subido_por',
                'created_at',
            ]);

        return Inertia::render(
            'Expedientes/Documentos',
            [
                'expediente' => $expediente,
                'documentos' => $documentos,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORIAL
    |--------------------------------------------------------------------------
    */

    public function historial(Expediente $expediente)
    {
        $usuario = $this->usuarioAutenticado();

        abort_unless(
            $usuario &&
            in_array(
                $usuario->rol,
                ['administrador', 'secretario', 'asesor'],
                true
            ),
            403,
            'No tiene autorización para consultar el historial.'
        );

        $this->autorizarExpediente($expediente);

        $historial = HistorialExpediente::query()
            ->where('expediente_id', $expediente->id)
            ->with([
                'usuario:id,nombre,apellido,rol',
            ])
            ->orderByDesc('fecha_hora')
            ->get([
                'id',
                'expediente_id',
                'usuario_id',
                'accion',
                'descripcion',
                'detalles',
                'ip_address',
                'user_agent',
                'fecha_hora',
            ]);

        return Inertia::render(
            'Expedientes/Historial',
            [
                'expediente' => [
                    'id' => $expediente->id,
                    'numero_expediente' => $expediente->numero_expediente,
                    'cliente' => $expediente->cliente,
                    'estado' => $expediente->estado,
                ],
                'historial' => $historial,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORTAR EXCEL
    |--------------------------------------------------------------------------
    */

    public function exportarExcel(Request $request)
    {
        $query = Expediente::with([
            'asesor',
            'creador',
            'modificador',
        ]);

        if ($request->filled('tipo_proceso')) {
            $query->where(
                'tipo_proceso',
                $request->tipo_proceso
            );
        }

        if ($request->filled('estado')) {
            $query->where(
                'estado',
                $request->estado
            );
        }

        if ($request->filled('asesor_id')) {
            $query->where(
                'asesor_id',
                $request->asesor_id
            );
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where(
                    'numero_expediente',
                    'like',
                    '%' . $buscar . '%'
                )->orWhere(
                    'cliente',
                    'like',
                    '%' . $buscar . '%'
                );
            });
        }

        $expedientes = $query
            ->orderByDesc('created_at')
            ->get();

        $nombre = 'expedientes_' .
            date('Y-m-d_H-i-s') .
            '.xls';

        $contenido = '<?xml version="1.0"?>';

        $contenido .=
            '<?mso-application progid="Excel.Sheet"?>';

        $contenido .=
            '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ' .
            'xmlns:o="urn:schemas-microsoft-com:office:office" ' .
            'xmlns:x="urn:schemas-microsoft-com:office:excel" ' .
            'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';

        $contenido .= '<Worksheet ss:Name="Expedientes">';
        $contenido .= '<Table>';

        $encabezados = [
            'Número de Expediente',
            'Cliente',
            'Tipo de Proceso',
            'Estado',
            'Asesor',
            'Fecha de Ingreso',
            'Creado por',
            'Última Modificación',
            'Modificado por',
        ];

        $contenido .= '<Row>';

        foreach ($encabezados as $encabezado) {
            $contenido .= '<Cell>';

            $contenido .=
                '<Data ss:Type="String">' .
                htmlspecialchars(
                    $encabezado,
                    ENT_XML1,
                    'UTF-8'
                ) .
                '</Data>';

            $contenido .= '</Cell>';
        }

        $contenido .= '</Row>';

        foreach ($expedientes as $expediente) {

            $asesor = $expediente->asesor
                ? $expediente->asesor->nombre . ' ' .
                  $expediente->asesor->apellido
                : 'Sin asignar';

            $creador = $expediente->creador
                ? $expediente->creador->nombre . ' ' .
                  $expediente->creador->apellido
                : 'Sistema';

            $modificador = $expediente->modificador
                ? $expediente->modificador->nombre . ' ' .
                  $expediente->modificador->apellido
                : 'N/A';

            $datos = [
                $expediente->numero_expediente,
                $expediente->cliente,
                $expediente->tipo_proceso,
                $expediente->estado,
                $asesor,
                optional($expediente->fecha_ingreso)
                    ->format('Y-m-d'),
                $creador,
                optional($expediente->updated_at)
                    ->format('Y-m-d H:i:s'),
                $modificador,
            ];

            $contenido .= '<Row>';

            foreach ($datos as $dato) {
                $contenido .= '<Cell>';

                $contenido .=
                    '<Data ss:Type="String">' .
                    htmlspecialchars(
                        (string) $dato,
                        ENT_XML1,
                        'UTF-8'
                    ) .
                    '</Data>';

                $contenido .= '</Cell>';
            }

            $contenido .= '</Row>';
        }

        $contenido .= '</Table>';
        $contenido .= '</Worksheet>';
        $contenido .= '</Workbook>';

        return response($contenido)
            ->header(
                'Content-Type',
                'application/vnd.ms-excel; charset=UTF-8'
            )
            ->header(
                'Content-Disposition',
                'attachment; filename="' . $nombre . '"'
            );
    }
}