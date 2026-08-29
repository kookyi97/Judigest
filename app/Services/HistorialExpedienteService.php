<?php

namespace App\Services;

use App\Models\HistorialExpediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistorialExpedienteService
{
    public function registrar(
        int $expedienteId,
        string $accion,
        string $descripcion,
        array $detalles = [],
        ?Request $request = null
    ): HistorialExpediente {
        $request ??= request();

        return HistorialExpediente::create([
            'expediente_id' => $expedienteId,
            'usuario_id' => Auth::id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'detalles' => $detalles ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'fecha_hora' => now(),
        ]);
    }
}