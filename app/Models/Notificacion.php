<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'titulo',
        'mensaje',
        'expediente_id',
        'audiencia_id',
        'leida',
        'estado_envio',
    ];

    protected $casts = [
        'leida' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function expediente()
    {
        return $this->belongsTo(Expediente::class);
    }

    public function audiencia()
    {
        return $this->belongsTo(Audiencia::class);
    }

    // Helper estático para crear notificación de audiencia al practicante
    public static function notificarAudiencia(
        int $usuarioId,
        int $expedienteId,
        int $audienciaId,
        string $tipo,        // 'audiencia_registrada' | 'audiencia_modificada'
        string $titulo,
        string $mensaje
    ): self {
        try {
            $notif = self::create([
                'usuario_id'   => $usuarioId,
                'tipo'         => $tipo,
                'titulo'       => $titulo,
                'mensaje'      => $mensaje,
                'expediente_id'=> $expedienteId,
                'audiencia_id' => $audienciaId,
                'leida'        => false,
                'estado_envio' => 'enviada',
            ]);
            return $notif;
        } catch (\Throwable $e) {
            // Si falla la notificación no debe romper el flujo principal
            // Se registra el fallo pero se deja pasar
            \Illuminate\Support\Facades\Log::error('Error al crear notificación: ' . $e->getMessage());
            return new self(['estado_envio' => 'fallida']);
        }
    }
}
