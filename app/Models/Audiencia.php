<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audiencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'audiencias';

    protected $fillable = [
        'expediente_id',
        'registrado_por',
        'fecha',
        'hora',
        'tipo_audiencia',
        'sala_juzgado',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function expediente()
    {
        return $this->belongsTo(Expediente::class);
    }

    public function registrador()
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class);
    }

    // Devuelve fecha y hora formateadas juntas
    public function getFechaHoraFormateadaAttribute(): string
    {
        return $this->fecha->format('d/m/Y') . ' ' . substr($this->hora, 0, 5);
    }

    // Detecta si esta audiencia tiene conflicto de horario con otra del mismo expediente
    public function tieneConflicto(): bool
    {
        return Audiencia::where('expediente_id', $this->expediente_id)
            ->where('id', '!=', $this->id)
            ->where('fecha', $this->fecha)
            ->where('estado', 'programada')
            ->exists();
    }
}
