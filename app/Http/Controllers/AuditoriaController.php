<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditoriaController extends Controller
{
    /**
     * Muestra la bitácora inmutable de acciones del sistema con filtros avanzados y paginación.
     */
    public function index(Request $request): Response
    {
        abort_unless(
            Auth::user()?->rol === 'administrador',
            403,
            'Acceso restringido exclusivamente a Administradores del sistema.'
        );

        $orden = in_array(strtolower((string) $request->input('orden', 'desc')), ['asc', 'desc'], true)
            ? strtolower((string) $request->input('orden', 'desc'))
            : 'desc';

        $query = $this->construirQueryFiltros($request);

        $auditorias = $query->orderBy('fecha_hora', $orden)
            ->orderBy('id', $orden)
            ->paginate(20)
            ->withQueryString()
            ->through(function ($item) {
                $esCritica = $item->esCriticaOSospechosa();
                $motivoCritica = $item->motivoSospecha();

                return [
                    'id' => $item->id,
                    'usuario' => $item->usuario ? [
                        'id' => $item->usuario->id,
                        'nombre' => "{$item->usuario->nombre} {$item->usuario->apellido}",
                        'rol' => $item->usuario->rol,
                        'correo' => $item->usuario->correo,
                    ] : [
                        'id' => null,
                        'nombre' => $item->usuario_nombre ?? 'Sistema / Anónimo',
                        'rol' => $item->usuario_rol ?? 'sistema',
                        'correo' => 'N/A',
                    ],
                    'modulo' => $item->modulo,
                    'accion' => $item->accion,
                    'tipo_accion' => $item->accion,
                    'descripcion' => $item->descripcion,
                    'entidad_tipo' => $item->entidad_tipo,
                    'entidad_id' => $item->entidad_id,
                    'detalles' => $item->detalles,
                    'detalles_legibles' => $this->formatearDetallesLegibles($item->detalles),
                    'ip_address' => $item->ip_address ?? 'N/A',
                    'user_agent' => $item->user_agent,
                    'user_agent_limpio' => $this->simplificarUserAgent($item->user_agent),
                    'resultado' => $item->resultado,
                    'es_critica' => $esCritica,
                    'motivo_critica' => $motivoCritica,
                    'nivel_riesgo' => $esCritica ? ($item->resultado === 'fallido' ? 'alto' : 'medio') : 'bajo',
                    'fecha_hora' => $item->fecha_hora ? $item->fecha_hora->format('d/m/Y H:i:s') : 'N/A',
                    'fecha' => $item->fecha_hora ? $item->fecha_hora->format('d/m/Y') : 'N/A',
                    'hora' => $item->fecha_hora ? $item->fecha_hora->format('H:i:s') : 'N/A',
                    'hace_tiempo' => $item->fecha_hora ? $item->fecha_hora->diffForHumans() : 'N/A',
                ];
            });

        // Metadatos para selectores de filtros
        $accionesDisponibles = Auditoria::distinct()
            ->whereNotNull('accion')
            ->orderBy('accion')
            ->pluck('accion');

        $modulosDisponibles = Auditoria::distinct()
            ->whereNotNull('modulo')
            ->orderBy('modulo')
            ->pluck('modulo');

        $usuariosDisponibles = Usuario::select('id', 'nombre', 'apellido', 'rol')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'nombre' => "{$u->nombre} {$u->apellido} ({$u->rol})",
            ]);

        // Estadísticas analíticas de trazabilidad
        $estadisticas = [
            'total' => Auditoria::count(),
            'hoy' => Auditoria::whereDate('fecha_hora', today())->count(),
            'exitosas' => Auditoria::where('resultado', 'exitoso')->count(),
            'fallidas' => Auditoria::where('resultado', 'fallido')->count(),
            'sospechosas' => Auditoria::soloSospechosas()->count(),
        ];

        return Inertia::render('Auditoria/Index', [
            'auditorias' => $auditorias,
            'filtros' => array_merge(
                $request->only([
                    'buscar',
                    'modulo',
                    'accion',
                    'usuario_id',
                    'resultado',
                    'periodo',
                    'desde',
                    'hasta',
                    'hora_desde',
                    'hora_hasta',
                    'solo_sospechosas',
                ]),
                [
                    'orden' => $orden,
                    'solo_sospechosas' => $request->boolean('solo_sospechosas'),
                ]
            ),
            'accionesDisponibles' => $accionesDisponibles,
            'modulosDisponibles' => $modulosDisponibles,
            'usuariosDisponibles' => $usuariosDisponibles,
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * Exporta la bitácora completa filtrada en formato Microsoft Excel (.xlsx) nativo para auditorías externas.
     */
    public function exportarExcel(Request $request)
    {
        abort_unless(
            Auth::user()?->rol === 'administrador',
            403,
            'Acceso restringido exclusivamente a Administradores del sistema.'
        );

        $orden = in_array(strtolower((string) $request->input('orden', 'desc')), ['asc', 'desc'], true)
            ? strtolower((string) $request->input('orden', 'desc'))
            : 'desc';

        $query = $this->construirQueryFiltros($request)
            ->orderBy('fecha_hora', $orden)
            ->orderBy('id', $orden);

        $registros = $query->get();

        // Orden limpio y estructurado de columnas para lectura en Excel
        $columnas = [
            'ID Registro',
            'Fecha y Hora',
            'Usuario Responsable',
            'Rol de Usuario',
            'Correo Electrónico',
            'Módulo',
            'Tipo de Acción',
            'Descripción de la Acción',
            'Resultado',
            'Nivel de Riesgo',
            'Motivo de Alerta',
            'Dirección IP',
            'Navegador / Sistema',
            'Entidad Afectada',
            'Detalles de la Acción',
        ];

        // Anchos optimizados para lectura limpia de cada columna en Excel
        $columnWidths = [12, 22, 24, 16, 26, 18, 26, 45, 16, 16, 30, 16, 24, 18, 55];

        $filas = [];
        foreach ($registros as $item) {
            $esCritica = $item->esCriticaOSospechosa();
            $motivoCritica = $item->motivoSospecha() ?? 'Normal';
            $nivelRiesgo = $esCritica ? ($item->resultado === 'fallido' ? 'ALTO' : 'MEDIO') : 'BAJO';

            $usuarioNombre = $item->usuario ? "{$item->usuario->nombre} {$item->usuario->apellido}" : ($item->usuario_nombre ?? 'Sistema / Anónimo');
            $usuarioRol = $item->usuario ? $item->usuario->rol : ($item->usuario_rol ?? 'sistema');
            $usuarioCorreo = $item->usuario ? $item->usuario->correo : 'N/A';

            $entidad = $item->entidad_tipo ? "{$item->entidad_tipo} #{$item->entidad_id}" : 'N/A';
            $detallesFormateados = $this->formatearDetallesLegibles($item->detalles);
            $navegadorLimpio = $this->simplificarUserAgent($item->user_agent);

            $filas[] = [
                'id' => (int) $item->id,
                'fecha_hora' => $item->fecha_hora ? $item->fecha_hora->format('Y-m-d H:i:s') : 'N/A',
                'usuario' => $usuarioNombre,
                'rol' => ucfirst($usuarioRol),
                'correo' => $usuarioCorreo,
                'modulo' => $item->modulo,
                'accion' => $item->accion,
                'descripcion' => $item->descripcion,
                'resultado' => ucfirst($item->resultado),
                'nivel_riesgo' => $nivelRiesgo,
                'motivo_alerta' => $motivoCritica,
                'ip' => $item->ip_address ?? 'N/A',
                'navegador' => $navegadorLimpio,
                'entidad' => $entidad,
                'detalles' => $detallesFormateados,
                '_es_critica' => $esCritica,
                '_es_fallido' => ($item->resultado === 'fallido'),
            ];
        }

        // Generar archivo .xlsx nativo (OpenXML)
        $tempPath = \App\Services\SimpleXlsxGenerator::create(
            'Bitacora_Auditoria',
            $columnas,
            $filas,
            $columnWidths
        );

        $nombreArchivo = 'bitacora_auditoria_' . date('Ymd_His') . '.xlsx';

        // Criterio 8: Registrar en la bitácora el evento de exportación realizado por el Administrador
        $admin = Auth::user();
        $totalExportados = count($filas);
        $filtrosAplicados = array_filter($request->only([
            'buscar', 'modulo', 'accion', 'usuario_id', 'resultado',
            'periodo', 'desde', 'hasta', 'hora_desde', 'hora_hasta', 'solo_sospechosas'
        ]));

        app(\App\Services\AuditoriaService::class)->registrar(
            modulo: 'Auditoría',
            accion: 'Exportación de Bitácora a Excel',
            descripcion: "El usuario Administrador ({$admin->nombre} {$admin->apellido}) exportó {$totalExportados} registro(s) de la bitácora de auditoría a archivo Excel (.xlsx).",
            detalles: [
                'formato' => 'xlsx',
                'total_registros_exportados' => $totalExportados,
                'filtros_aplicados' => $filtrosAplicados,
                'nombre_archivo' => $nombreArchivo,
                'ip' => $request->ip(),
            ],
            resultado: 'exitoso',
            request: $request,
            usuario: $admin
        );

        return response()->download($tempPath, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Exporta la bitácora en formato CSV con delimitador compatible con Excel en español y streaming en bloques.
     */
    public function exportarCsv(Request $request): StreamedResponse
    {
        abort_unless(
            Auth::user()?->rol === 'administrador',
            403,
            'Acceso restringido exclusivamente a Administradores del sistema.'
        );

        $orden = in_array(strtolower((string) $request->input('orden', 'desc')), ['asc', 'desc'], true)
            ? strtolower((string) $request->input('orden', 'desc'))
            : 'desc';

        $query = $this->construirQueryFiltros($request)
            ->orderBy('fecha_hora', $orden)
            ->orderBy('id', $orden);

        $nombreArchivo = 'bitacora_auditoria_' . date('Ymd_His') . '.csv';

        // Criterio 8: Registrar en la bitácora el evento de exportación realizado por el Administrador
        $admin = Auth::user();
        $totalEstimado = (clone $query)->count();
        $filtrosAplicados = array_filter($request->only([
            'buscar', 'modulo', 'accion', 'usuario_id', 'resultado',
            'periodo', 'desde', 'hasta', 'hora_desde', 'hora_hasta', 'solo_sospechosas'
        ]));

        app(\App\Services\AuditoriaService::class)->registrar(
            modulo: 'Auditoría',
            accion: 'Exportación de Bitácora a CSV',
            descripcion: "El usuario Administrador ({$admin->nombre} {$admin->apellido}) exportó {$totalEstimado} registro(s) de la bitácora de auditoría a archivo plano CSV.",
            detalles: [
                'formato' => 'csv',
                'total_registros_exportados' => $totalEstimado,
                'filtros_aplicados' => $filtrosAplicados,
                'nombre_archivo' => $nombreArchivo,
                'ip' => $request->ip(),
            ],
            resultado: 'exitoso',
            request: $request,
            usuario: $admin
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $controller = $this;

        return response()->streamDownload(function () use ($query, $controller) {
            $handle = fopen('php://output', 'w');

            // 1. BOM UTF-8 al inicio exacto para apertura correcta con acentos y tildes en Microsoft Excel
            fputs($handle, "\xEF\xBB\xBF");

            // 2. Encabezados ordenados y claros
            fputcsv($handle, [
                'ID Registro',
                'Fecha y Hora',
                'Usuario Responsable',
                'Rol de Usuario',
                'Correo Electrónico',
                'Módulo',
                'Tipo de Acción',
                'Descripción de la Acción',
                'Resultado',
                'Nivel de Riesgo',
                'Motivo de Alerta',
                'Dirección IP',
                'Navegador / Sistema',
                'Entidad Afectada',
                'Detalles de la Acción',
            ], ';');

            // 4. Streaming en bloques de 500 registros con delimitador ';'
            $query->chunk(500, function ($registros) use ($handle, $controller) {
                foreach ($registros as $item) {
                    $esCritica = $item->esCriticaOSospechosa();
                    $motivoCritica = $item->motivoSospecha() ?? 'Normal';
                    $nivelRiesgo = $esCritica ? ($item->resultado === 'fallido' ? 'ALTO' : 'MEDIO') : 'BAJO';

                    $usuarioNombre = $item->usuario ? "{$item->usuario->nombre} {$item->usuario->apellido}" : ($item->usuario_nombre ?? 'Sistema / Anónimo');
                    $usuarioRol = $item->usuario ? $item->usuario->rol : ($item->usuario_rol ?? 'sistema');
                    $usuarioCorreo = $item->usuario ? $item->usuario->correo : 'N/A';

                    $entidad = $item->entidad_tipo ? "{$item->entidad_tipo} #{$item->entidad_id}" : 'N/A';
                    $detallesFormateados = $controller->formatearDetallesLegibles($item->detalles);
                    $navegadorLimpio = $controller->simplificarUserAgent($item->user_agent);

                    $fechaFormateada = $item->fecha_hora ? '="' . $item->fecha_hora->format('Y-m-d H:i:s') . '"' : 'N/A';

                    fputcsv($handle, [
                        $item->id,
                        $fechaFormateada,
                        $usuarioNombre,
                        ucfirst($usuarioRol),
                        $usuarioCorreo,
                        $item->modulo,
                        $item->accion,
                        $item->descripcion,
                        ucfirst($item->resultado),
                        $nivelRiesgo,
                        $motivoCritica,
                        $item->ip_address ?? 'N/A',
                        $navegadorLimpio,
                        $entidad,
                        $detallesFormateados,
                    ], ';');
                }
            });

            fclose($handle);
        }, $nombreArchivo, $headers);
    }

    /**
     * Convierte el array o JSON de detalles técnicos a texto limpio y legible para humanos en Excel y CSV.
     */
    public function formatearDetallesLegibles(mixed $detalles): string
    {
        if (empty($detalles)) {
            return 'Sin detalles adicionales';
        }

        if (is_string($detalles)) {
            $decoded = json_decode($detalles, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $detalles = $decoded;
            } else {
                return $detalles;
            }
        }

        if (!is_array($detalles)) {
            return (string) $detalles;
        }

        $mapaClaves = [
            'ruta' => 'Ruta',
            'metodo' => 'Método',
            'uri' => 'URI',
            'status_code' => 'Código HTTP',
            'roles_permitidos' => 'Roles requeridos',
            'rol_usuario' => 'Rol usuario',
            'correo' => 'Correo',
            'intentos' => 'Intentos fallidos',
            'bloqueado' => 'Cuenta bloqueada',
            'ip' => 'IP origen',
            'formato' => 'Formato exportación',
            'total_registros_exportados' => 'Registros exportados',
            'filtros_aplicados' => 'Filtros aplicados',
            'nombre_archivo' => 'Archivo',
            'numero_expediente' => 'Expediente',
            'estado' => 'Estado',
            'motivo' => 'Motivo',
        ];

        $partes = [];
        foreach ($detalles as $clave => $valor) {
            $etiqueta = $mapaClaves[$clave] ?? ucfirst(str_replace('_', ' ', (string) $clave));

            if (is_bool($valor)) {
                $valorTexto = $valor ? 'Sí' : 'No';
            } elseif (is_array($valor)) {
                if (empty($valor)) {
                    continue;
                }
                // Si es un array asociativo como filtros_aplicados
                if (array_keys($valor) !== range(0, count($valor) - 1)) {
                    $subpartes = [];
                    foreach ($valor as $subK => $subV) {
                        $subpartes[] = "{$subK}: " . (is_array($subV) ? json_encode($subV) : $subV);
                    }
                    $valorTexto = implode(', ', $subpartes);
                } else {
                    $valorTexto = implode(', ', $valor);
                }
            } elseif ($valor === null) {
                $valorTexto = 'N/A';
            } else {
                $valorTexto = (string) $valor;
            }

            $partes[] = "{$etiqueta}: {$valorTexto}";
        }

        return !empty($partes) ? implode(' | ', $partes) : 'Sin detalles adicionales';
    }

    /**
     * Convierte User-Agent extenso en una descripción legible de navegador y sistema operativo.
     */
    public function simplificarUserAgent(?string $ua): string
    {
        if (empty($ua) || $ua === 'N/A') {
            return 'N/A';
        }

        $so = 'Desconocido';
        if (stripos($ua, 'Windows NT 10.0') !== false) $so = 'Windows 10/11';
        elseif (stripos($ua, 'Windows') !== false) $so = 'Windows';
        elseif (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS') !== false) $so = 'macOS';
        elseif (stripos($ua, 'Android') !== false) $so = 'Android';
        elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $so = 'iOS';
        elseif (stripos($ua, 'Linux') !== false) $so = 'Linux';

        $nav = 'Navegador';
        if (stripos($ua, 'Edg/') !== false) $nav = 'Microsoft Edge';
        elseif (stripos($ua, 'Chrome/') !== false) $nav = 'Google Chrome';
        elseif (stripos($ua, 'Firefox/') !== false) $nav = 'Mozilla Firefox';
        elseif (stripos($ua, 'Safari/') !== false) $nav = 'Apple Safari';
        elseif (stripos($ua, 'Postman') !== false) $nav = 'Postman API';
        elseif (stripos($ua, 'PHPUnit') !== false) $nav = 'Prueba Automatizada';

        return "{$nav} ({$so})";
    }

    /**
     * Construye la consulta unificada de filtros para index y exportaciones.
     */
    protected function construirQueryFiltros(Request $request)
    {
        $query = Auditoria::query()->with('usuario:id,nombre,apellido,rol,correo');

        // Búsqueda libre
        if ($termino = $request->input('buscar')) {
            $query->buscar($termino);
        }

        // Filtro por módulo
        if ($modulo = $request->input('modulo')) {
            $query->modulo($modulo);
        }

        // Filtro por usuario específico
        if ($usuarioId = $request->input('usuario_id')) {
            $query->usuario((int) $usuarioId);
        }

        // Filtro por tipo de acción
        if ($accion = $request->input('accion')) {
            $query->accion($accion);
        }

        // Filtro por resultado
        if ($resultado = $request->input('resultado')) {
            $query->resultado($resultado);
        }

        // Filtro por período rápido
        $filtroFecha = $request->input('periodo', 'todos');
        match ($filtroFecha) {
            'hoy' => $query->whereDate('fecha_hora', today()),
            'semana' => $query->where('fecha_hora', '>=', now()->subDays(7)),
            'mes' => $query->where('fecha_hora', '>=', now()->subDays(30)),
            default => null,
        };

        // Filtro por rango de fechas
        if ($desde = $request->input('desde')) {
            $query->whereDate('fecha_hora', '>=', $desde);
        }

        if ($hasta = $request->input('hasta')) {
            $query->whereDate('fecha_hora', '<=', $hasta);
        }

        // Filtro por rango de horas (HH:MM o HH:MM:SS)
        if ($horaDesde = $request->input('hora_desde')) {
            $query->horaDesde($horaDesde);
        }

        if ($horaHasta = $request->input('hora_hasta')) {
            $query->horaHasta($horaHasta);
        }

        // Filtro para detectar actividades sospechosas o anomalías
        if ($request->boolean('solo_sospechosas')) {
            $query->soloSospechosas(true);
        }

        return $query;
    }
}
