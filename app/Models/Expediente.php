<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'fecha_ingreso',
        'creado_por',
        'descripcion',
        'estado',
        'modificado_por',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
    ];

    public function asesor()
    {
        return $this->belongsTo(Usuario::class, 'asesor_id');
    }

    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public function modificador()
    {
        return $this->belongsTo(Usuario::class, 'modificado_por');
    }
}
