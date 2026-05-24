<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
        abort(403, 'No tienes permisos para acceder a esta sección.');
    }

    return $next($request);
}
}
