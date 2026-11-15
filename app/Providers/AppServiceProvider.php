<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // JD050: en producción todo el sitio (incluido el formulario de contraseña) se sirve por HTTPS.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // JD049 / JD050: límites de peticiones por usuario autenticado (o por IP si no hay sesión).
        RateLimiter::for('perfil-actualizar', fn (Request $r) => Limit::perMinute(10)->by('perfil:' . ($r->user()?->id ?? $r->ip())));
        RateLimiter::for('perfil-password', fn (Request $r) => Limit::perMinute(5)->by('password:' . ($r->user()?->id ?? $r->ip())));

        try {
            if (!app()->runningInConsole() || app()->runningUnitTests()) {
                $tiempoSesion = (int) app(\App\Services\ConfiguracionService::class)->get('seguridad_tiempo_sesion_minutos', 60);
                if ($tiempoSesion > 0) {
                    config(['session.lifetime' => $tiempoSesion]);
                }
            }
        } catch (\Throwable $e) {
            // Silencioso ante comandos de consola previa migración
        }
    }
}