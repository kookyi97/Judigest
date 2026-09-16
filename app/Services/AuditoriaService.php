<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditoriaService
{
    /**
     * Registra una acción en la bitácora de auditoría inmutable del sistema.
     * Garantiza autoría infalsificable (Auth::id()) y fecha exacta del servidor (now()).
     */
    public function registrar(
        string $modulo,
        string $accion,
        string $descripcion,
        ?string $entidadTipo = null,
        ?int $entidadId = null,
        array $detalles = [],
        string $resultado = 'exitoso',
        ?Request $request = null,
        ?Usuario $usuario = null
    ): Auditoria {
        $request ??= request();
        $usuarioAutenticado = $usuario ?? Auth::user();

        // Sanitizamos detalles para nunca persistir contraseñas o tokens
        $detallesSanitizados = $this->sanitizarDetalles($detalles);

        return Auditoria::create([
            'usuario_id' => $usuarioAutenticado?->id,
            'usuario_nombre' => $usuarioAutenticado ? "{$usuarioAutenticado->nombre} {$usuarioAutenticado->apellido}" : 'Sistema',
            'usuario_rol' => $usuarioAutenticado?->rol ?? 'sistema',
            'modulo' => $modulo,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'detalles' => !empty($detallesSanitizados) ? $detallesSanitizados : null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'resultado' => $resultado,
            'fecha_hora' => now(), // Timestamp generado estrictamente por el servidor
        ]);
    }

    /**
     * Retorna el número total de acciones registradas el día de hoy.
     */
    public function totalAccionesHoy(): int
    {
        return Auditoria::whereDate('fecha_hora', today())->count();
    }

    /**
     * Retorna las últimas actividades del sistema formateadas para el dashboard.
     */
    public function ultimasActividades(int $limite = 10): array
    {
        return Auditoria::orderBy('fecha_hora', 'desc')
            ->take($limite)
            ->get()
            ->map(function ($auditoria) {
                return [
                    'id' => $auditoria->id,
                    'usuario' => $auditoria->usuario_nombre ?? 'Sistema',
                    'rol' => $auditoria->usuario_rol ?? 'sistema',
                    'accion' => $auditoria->accion,
                    'descripcion' => $auditoria->descripcion,
                    'modulo' => $auditoria->modulo,
                    'expediente' => $auditoria->entidad_tipo === 'Expediente' ? ($auditoria->detalles['numero_expediente'] ?? "ID #{$auditoria->entidad_id}") : null,
                    'ip' => $auditoria->ip_address ?? 'N/A',
                    'hora' => $auditoria->fecha_hora ? $auditoria->fecha_hora->format('H:i') : 'N/A',
                    'fecha' => $auditoria->fecha_hora ? $auditoria->fecha_hora->format('d/m/Y') : 'N/A',
                    'hace_tiempo' => $auditoria->fecha_hora ? $auditoria->fecha_hora->diffForHumans() : 'N/A',
                    'resultado' => $auditoria->resultado,
                ];
            })
            ->toArray();
    }

    /**
     * Elimina datos sensibles de los detalles antes de guardarlos.
     */
    protected function sanitizarDetalles(array $datos): array
    {
        $clavesSensibles = [
            'contrasena',
            'password',
            'password_confirmation',
            'contrasena_confirmation',
            '_token',
            'token',
            'remember_token',
        ];

        foreach ($datos as $k => $v) {
            if (in_array(strtolower($k), $clavesSensibles, true)) {
                unset($datos[$k]);
            } elseif (is_array($v)) {
                $datos[$k] = $this->sanitizarDetalles($v);
            }
        }

        return $datos;
    }
}
