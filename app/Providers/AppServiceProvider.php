<?php

namespace App\Providers;

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
