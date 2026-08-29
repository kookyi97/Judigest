<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialExpediente extends Model
{
    use HasFactory;

    protected $table = 'historial_expedientes';

    protected $fillable = [
        'expediente_id',
        'usuario_id',
        'accion',
        'descripcion',
        'detalles',
        'ip_address',
        'user_agent',
        'fecha_hora',
    ];

    protected $casts = [
        'detalles' => 'array',
        'fecha_hora' => 'datetime',
    ];


    protected static function booted(): void
{
    static::deleting(function () {
        throw new \RuntimeException(
            'Los registros del historial no pueden eliminarse desde la aplicación.'
        );
    });

    static::updating(function () {
        throw new \RuntimeException(
            'Los registros del historial no pueden modificarse desde la aplicación.'
        );
    });
}


    public function expediente()
    {
        return $this->belongsTo(Expediente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}