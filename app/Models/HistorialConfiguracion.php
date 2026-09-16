<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialConfiguracion extends Model
{
    use HasFactory;

    protected $table = 'historial_configuraciones';

    protected $fillable = [
        'configuracion_id',
        'parametro_clave',
        'parametro_nombre',
        'categoria',
        'valor_anterior',
        'valor_nuevo',
        'usuario_id',
        'ip_address',
        'user_agent',
        'fecha_hora',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function configuracion()
    {
        return $this->belongsTo(Configuracion::class, 'configuracion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
