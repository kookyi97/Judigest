<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\RegistrarAuditoriaMiddleware::class,
        ]);
        
        $middleware->alias([
            'rol' => \App\Http\Middleware\VerificarRol::class,
            'sin.cache' => \App\Http\Middleware\SinCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // JD050: sesión expirada (419) o demasiadas peticiones (429) en formularios Inertia → mensaje en español.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (!$request->inertia()) {
                return $response;
            }

            return match ($response->getStatusCode()) {
                419 => $request->user()
                    ? back()->withErrors(['general' => 'La página expiró. Recárgala e inténtalo de nuevo.'])
                    : redirect()->route('login')->withErrors(['correo' => 'Tu sesión expiró. Inicia sesión nuevamente.']),
                429 => back()->withErrors(['general' => 'Demasiadas solicitudes. Espera un momento e inténtalo de nuevo.']),
                default => $response,
            };
        });
    })->create();