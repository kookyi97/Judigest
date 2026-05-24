<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAcceso extends Model
{
     protected $table = 'log_accesos';
    protected $fillable = ['usuario_id','accion','ip_address','navegador','fecha_hora'];
    protected $casts = ['fecha_hora' => 'datetime'];

    public function usuario() {
        return $this->belongsTo(Usuario::class);
    }
}
