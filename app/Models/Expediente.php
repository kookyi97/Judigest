<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expediente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expedientes';

    protected $fillable = [
    'numero_expediente',
    'cliente',
    'tipo_proceso',
    'asesor_id',
    'practicante_id',
    'fecha_ingreso',
    'creado_por',
    'descripcion',
    'estado',
    'modificado_por',
];

    protected $casts = [
     'fecha_ingreso' => 'date',
    ];

    /** Estados en los que el expediente ya no admite cambios de asignación. */
    public const ESTADOS_FINALES = ['Cerrado', 'Archivado'];

    /** Avance (%) que representa cada estado del expediente. */
    public const AVANCE_POR_ESTADO = [
        'Abierto'    => 10,
        'En Proceso' => 50,
        'Resuelto'   => 90,
        'Cerrado'    => 100,
        'Archivado'  => 100,
    ];

    /** Se envía al frontend junto con el resto de atributos. */
    protected $appends = ['avance'];

    public function getAvanceAttribute(): int
    {
        return self::AVANCE_POR_ESTADO[$this->estado] ?? 0;
    }

    /*
    |--------------------------------------------------------------------------
    | VISIBILIDAD POR ROL (única fuente de verdad)
    |--------------------------------------------------------------------------
    | Cualquier listado de expedientes debe pasar por este scope.
    | Si el rol no se reconoce, no se devuelve nada (fail-closed).
    */
    public function scopeVisiblesPara(Builder $query, ?Usuario $usuario): Builder
    {
        return match ($usuario?->rol) {
            'administrador', 'secretario' => $query,
            'asesor'                      => $query->where('asesor_id', $usuario->id),
            'practicante'                 => $query->where('practicante_id', $usuario->id),
            default                       => $query->whereRaw('1 = 0'),
        };
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereNotIn('estado', self::ESTADOS_FINALES);
    }

    /** Misma regla que el scope, pero para un expediente concreto. */
    public function esVisiblePara(?Usuario $usuario): bool
    {
        return match ($usuario?->rol) {
            'administrador', 'secretario' => true,
            'asesor'                      => (int) $this->asesor_id === (int) $usuario->id,
            'practicante'                 => (int) $this->practicante_id === (int) $usuario->id,
            default                       => false,
        };
    }

    public function asesor()
    {
        return $this->belongsTo(Usuario::class, 'asesor_id');
    }

    public function practicante()
    {   
        return $this->belongsTo(Usuario::class, 'practicante_id');
    }

    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public function modificador()
    {
        return $this->belongsTo(Usuario::class, 'modificado_por');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'expediente_id');
    }

    public function historial()
    {
        return $this->hasMany(HistorialExpediente::class, 'expediente_id');
    }
}