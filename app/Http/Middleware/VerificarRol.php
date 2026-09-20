<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class VerificarRol
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $usuario = Auth::user();

    if (!in_array($usuario->rol, $roles)) {
        app(\App\Services\AuditoriaService::class)->registrar(
            modulo: 'Seguridad',
            accion: 'Intento de Acceso No Autorizado',
            descripcion: "El usuario {$usuario->nombre} {$usuario->apellido} con rol '{$usuario->rol}' intentó acceder a la ruta '{$request->path()}' restringida a los roles: " . implode(', ', $roles) . ".",
            detalles: [
                'ruta' => $request->path(),
                'metodo' => $request->method(),
                'roles_permitidos' => $roles,
                'rol_usuario' => $usuario->rol,
                'ip' => $request->ip(),
            ],
            resultado: 'fallido',
            request: $request,
            usuario: $usuario
        );

        abort(403, 'No tienes permisos para acceder a esta sección.');
    }

    return $next($request);
}
}
