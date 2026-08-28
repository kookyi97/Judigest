<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expediente;
use App\Models\Usuario;
use App\Models\Documento;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpedienteController extends Controller
{
    public function index()
    {
        $expedientes = Expediente::with([
            'asesor',
            'creador',
            'modificador'
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Lista de asesores para el filtro del listado.
        // Se incluyen todos los usuarios con rol asesor
        // para que también aparezcan los asesores de expedientes
        // que eventualmente estén inactivos.
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

        return Inertia::render('Expedientes/Create', [
            'asesores' => $asesores
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_expediente' => 'required|max:50|unique:expedientes,numero_expediente',
            'cliente'           => 'required|max:150',
            'tipo_proceso'      => 'required|in:Civil,Penal,Laboral,Familia,Administrativo',
            'asesor_id'         => 'required|exists:usuarios,id',
            'fecha_ingreso'     => 'required|date',
        ], [
            'numero_expediente.required' => 'El número de expediente es obligatorio.',
            'numero_expediente.unique'   => 'Este número de expediente ya existe en el sistema.',
            'cliente.required'           => 'La información del cliente es obligatoria.',
            'tipo_proceso.required'      => 'El tipo de proceso es obligatorio.',
            'asesor_id.required'         => 'Debe asignar un asesor responsable.',
            'asesor_id.exists'            => 'El asesor seleccionado no es válido.',
            'fecha_ingreso.required'     => 'La fecha de ingreso es obligatoria.',
        ]);

        $data = $request->all();
        $data['creado_por'] = Auth::id();

        Expediente::create($data);

        return redirect()
            ->route('expedientes.index')
            ->with('exito', 'Expediente registrado exitosamente.');
    }

    public function edit(Expediente $expediente)
    {
        $expediente->load([
            'asesor',
            'creador',
            'modificador',
            'documentos.usuario',
        ]);

        // Generamos la URL de cada documento para utilizarla
        // desde la vista Edit.
        $expediente->documentos->each(function ($documento) {
            $documento->url = route('documentos.ver', $documento->id);
        });

        $asesores = Usuario::where('rol', 'asesor')
            ->where('activo', true)
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();

        return Inertia::render(
            'Expedientes/Edit',
            [
                'expediente' => $expediente,
                'asesores' => $asesores,
            ]
        );
    }

    public function update(Request $request, Expediente $expediente)
    {
        $request->validate([
            'numero_expediente' => 'required|max:50|unique:expedientes,numero_expediente,' . $expediente->id,
            'cliente'           => 'required|max:150',
            'tipo_proceso'      => 'required|in:Civil,Penal,Laboral,Familia,Administrativo',
            'asesor_id'         => 'required|exists:usuarios,id',
            'fecha_ingreso'     => 'required|date',
            'estado'            => 'required|in:Abierto,En Proceso,Resuelto,Cerrado,Archivado',
            'descripcion'       => 'nullable|string',
        ], [
            'numero_expediente.required' => 'El número de expediente es obligatorio.',
            'numero_expediente.unique'   => 'Este número de expediente ya existe en el sistema.',
            'cliente.required'           => 'La información del cliente es obligatoria.',
            'tipo_proceso.required'      => 'El tipo de proceso es obligatorio.',
            'asesor_id.required'         => 'Debe asignar un asesor responsable.',
            'asesor_id.exists'           => 'El asesor seleccionado no es válido.',
            'fecha_ingreso.required'     => 'La fecha de ingreso es obligatoria.',
            'estado.required'            => 'El estado es obligatorio.',
            'estado.in'                  => 'El estado seleccionado no es válido.',
        ]);

        $data = $request->all();
        $data['modificado_por'] = Auth::id();

        $expediente->update($data);

        return redirect()
            ->route('expedientes.index')
            ->with('exito', 'Expediente actualizado exitosamente.');
    }

    public function cambiarEstado(Request $request, Expediente $expediente)
    {
        $request->validate([
            'estado' => 'required|in:Abierto,En Proceso,Resuelto,Cerrado,Archivado',
        ]);

        $expediente->update([
            'estado' => $request->estado,
            'modificado_por' => Auth::id(),
        ]);

        return back()->with(
            'exito',
            'El estado del expediente fue actualizado correctamente.'
        );
    }

    public function archivar(Expediente $expediente)
    {
        $expediente->update([
            'estado' => 'Archivado',
            'modificado_por' => Auth::id(),
        ]);

        return back()->with(
            'exito',
            'El expediente fue archivado correctamente.'
        );
    }

    public function show(Expediente $expediente)
    {
        $expediente->load([
            'asesor',
            'creador',
            'modificador',
            'documentos.usuario',
        ]);

        $expediente->documentos->each(function ($documento) {
            $documento->url = route('documentos.ver', $documento->id);
        });

        return Inertia::render('Expedientes/Show', [
            'expediente' => $expediente,
        ]);
    }

    /*
     * JD029 - Subir documento
     */
    public function subirDocumento(
        Request $request,
        Expediente $expediente
    ) {
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

        $nombreOriginal = $archivo->getClientOriginalName();

        $nombreArchivo = time() . '_' .
            preg_replace(
                '/[^A-Za-z0-9._-]/',
                '_',
                $nombreOriginal
            );

        $ruta = $archivo->storeAs(
            'documentos/' . $expediente->id,
            $nombreArchivo,
            'public'
        );

        Documento::create([
            'expediente_id' => $expediente->id,
            'nombre_original' => $nombreOriginal,
            'nombre_archivo' => $nombreArchivo,
            'ruta' => $ruta,
            'tipo_mime' => $archivo->getClientMimeType(),
            'tamano' => $archivo->getSize(),
            'subido_por' => Auth::id(),
        ]);

        return back()->with(
            'exito',
            'Documento subido correctamente.'
        );
    }

    /*
     * JD029 - Ver documento
     *
     * Se utiliza response()->file() para que el navegador
     * conserve el nombre original del archivo al abrirlo.
     */
    public function verDocumento(Documento $documento)
    {
        if (!Storage::disk('public')->exists($documento->ruta)) {
            abort(404, 'El documento no fue encontrado.');
        }

        $rutaCompleta = Storage::disk('public')->path(
            $documento->ruta
        );

        $mime = $documento->tipo_mime
            ?: Storage::disk('public')->mimeType($documento->ruta);

        $tiposVisibles = [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ];

        $disposition = in_array($mime, $tiposVisibles)
            ? 'inline'
            : 'attachment';

        $nombreOriginal = $documento->nombre_original;

        $nombreAscii = preg_replace(
            '/[^A-Za-z0-9._-]/',
            '_',
            $nombreOriginal
        );

        $nombreUtf8 = rawurlencode($nombreOriginal);

        return response()->file(
            $rutaCompleta,
            [
                'Content-Type' => $mime,
                'Content-Disposition' =>
                    $disposition .
                    '; filename="' .
                    $nombreAscii .
                    '"; filename*=UTF-8\'\'' .
                    $nombreUtf8,
            ]
        );
    }

    /*
     * Exportar expedientes a Excel
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
                );

                $q->orWhere(
                    'cliente',
                    'like',
                    '%' . $buscar . '%'
                );
            });
        }

        $expedientes = $query
            ->orderBy('created_at', 'desc')
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
                htmlspecialchars($encabezado) .
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
                optional($expediente->fecha_ingreso)->format('Y-m-d'),
                $creador,
                optional($expediente->updated_at)->format('Y-m-d H:i:s'),
                $modificador,
            ];

            $contenido .= '<Row>';

            foreach ($datos as $dato) {
                $contenido .= '<Cell>';

                $contenido .=
                    '<Data ss:Type="String">' .
                    htmlspecialchars((string) $dato) .
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