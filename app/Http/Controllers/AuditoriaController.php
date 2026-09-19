<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    /**
     * Muestra la bitácora inmutable de acciones del sistema con filtros avanzados.
     */
    public function index(Request $request): Response
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

        // Filtro por usuario
        if ($usuarioId = $request->input('usuario_id')) {
            $query->usuario($usuarioId);
        }

        // Filtro por tipo de acción
        if ($accion = $request->input('accion')) {
            $query->accion($accion);
        }

        // Filtro por resultado
        if ($resultado = $request->input('resultado')) {
            $query->resultado($resultado);
        }

        // Filtro por fecha
        $filtroFecha = $request->input('periodo', 'todos');
        match ($filtroFecha) {
            'hoy' => $query->whereDate('fecha_hora', today()),
            'semana' => $query->where('fecha_hora', '>=', now()->subDays(7)),
            'mes' => $query->where('fecha_hora', '>=', now()->subDays(30)),
            default => null,
        };

        if ($desde = $request->input('desde')) {
            $query->whereDate('fecha_hora', '>=', $desde);
        }

        if ($hasta = $request->input('hasta')) {
            $query->whereDate('fecha_hora', '<=', $hasta);
        }

        // Orden cronológico (asc: más antiguos primero, desc: más recientes primero)
        $orden = in_array(strtolower((string) $request->input('orden', 'desc')), ['asc', 'desc'], true)
            ? strtolower((string) $request->input('orden', 'desc'))
            : 'desc';

        $auditorias = $query->orderBy('fecha_hora', $orden)
            ->orderBy('id', $orden)
            ->paginate(20)
            ->withQueryString()
            ->through(function ($item) {
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
                    'ip_address' => $item->ip_address ?? 'N/A',
                    'user_agent' => $item->user_agent,
                    'resultado' => $item->resultado,
                    'fecha_hora' => $item->fecha_hora ? $item->fecha_hora->format('d/m/Y H:i:s') : 'N/A',
                    'fecha' => $item->fecha_hora ? $item->fecha_hora->format('d/m/Y') : 'N/A',
                    'hora' => $item->fecha_hora ? $item->fecha_hora->format('H:i:s') : 'N/A',
                    'hace_tiempo' => $item->fecha_hora ? $item->fecha_hora->diffForHumans() : 'N/A',
                ];
            });

        // Metadatos para filtros
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

        // Estadísticas rápidas
        $estadisticas = [
            'total' => Auditoria::count(),
            'hoy' => Auditoria::whereDate('fecha_hora', today())->count(),
            'exitosas' => Auditoria::where('resultado', 'exitoso')->count(),
            'fallidas' => Auditoria::where('resultado', 'fallido')->count(),
        ];

        return Inertia::render('Auditoria/Index', [
            'auditorias' => $auditorias,
            'filtros' => array_merge(
                $request->only(['buscar', 'modulo', 'accion', 'usuario_id', 'resultado', 'periodo', 'desde', 'hasta']),
                ['orden' => $orden]
            ),
            'accionesDisponibles' => $accionesDisponibles,
            'modulosDisponibles' => $modulosDisponibles,
            'usuariosDisponibles' => $usuariosDisponibles,
            'estadisticas' => $estadisticas,
        ]);
    }
}
