<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'categoria',
        'tipo',
        'valor',
        'opciones',
        'modificado_por',
    ];

    protected $casts = [
        'opciones' => 'array',
    ];

    public function getValorTipadoAttribute()
    {
        return match ($this->tipo) {
            'number' => is_numeric($this->valor) ? (str_contains($this->valor, '.') ? (float) $this->valor : (int) $this->valor) : 0,
            'boolean' => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            'array' => json_decode($this->valor, true) ?? [],
            default => (string) $this->valor,
        };
    }

    public function setValorTipadoAttribute($value): void
    {
        $this->attributes['valor'] = match ($this->tipo) {
            'boolean' => $value ? '1' : '0',
            'array' => is_array($value) ? json_encode($value) : (string) $value,
            default => (string) $value,
        };
    }

    public function modificadoPor()
    {
        return $this->belongsTo(Usuario::class, 'modificado_por');
    }

    public function historial()
    {
        return $this->hasMany(HistorialConfiguracion::class, 'configuracion_id')->orderBy('fecha_hora', 'desc');
    }
}
