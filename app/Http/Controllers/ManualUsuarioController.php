<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManualUsuarioController extends Controller
{
    /**
     * Sirve de forma controlada el manual de usuario en PDF exclusivo según el rol autenticado (JD051).
     * Modo inline para visualizar directamente en el visor integrado del navegador.
     * Cabeceras no-cache y stream para evitar caché en navegador entre cambios de rol y compatibilidad Windows.
     */
    public function ver(Request $request): StreamedResponse
    {
        $rol = strtolower(trim((string) ($request->user()?->rol ?? 'usuario')));
        $rutaPdfRol = storage_path("app/manuales/manual_{$rol}.pdf");

        abort_unless(file_exists($rutaPdfRol), 404, "El manual de usuario para el rol [{$rol}] no se encuentra disponible.");

        $nombreDescarga = "Manual_de_Usuario_" . ucfirst($rol) . "_JUDIGEST.pdf";
        $tamano = filesize($rutaPdfRol);

        return response()->stream(function () use ($rutaPdfRol) {
            $stream = fopen($rutaPdfRol, 'rb');
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$nombreDescarga}\"",
            'Content-Length'      => $tamano,
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }

    /**
     * Descarga forzada del manual en PDF según el rol del usuario autenticado.
     */
    public function descargar(Request $request): StreamedResponse
    {
        $rol = strtolower(trim((string) ($request->user()?->rol ?? 'usuario')));
        $rutaPdfRol = storage_path("app/manuales/manual_{$rol}.pdf");

        abort_unless(file_exists($rutaPdfRol), 404, "El manual de usuario para el rol [{$rol}] no se encuentra disponible.");

        $nombreDescarga = "Manual_de_Usuario_" . ucfirst($rol) . "_JUDIGEST.pdf";
        $tamano = filesize($rutaPdfRol);

        return response()->streamDownload(function () use ($rutaPdfRol) {
            $stream = fopen($rutaPdfRol, 'rb');
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $nombreDescarga, [
            'Content-Type'   => 'application/pdf',
            'Content-Length' => $tamano,
            'Cache-Control'  => 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0',
            'Pragma'         => 'no-cache',
            'Expires'        => '0',
        ]);
    }
}

