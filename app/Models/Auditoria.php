<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    protected $fillable = [
        'usuario_id',
        'usuario_nombre',
        'usuario_rol',
        'modulo',
        'accion',
        'descripcion',
        'entidad_tipo',
        'entidad_id',
        'detalles',
        'ip_address',
        'user_agent',
        'resultado',
        'fecha_hora',
    ];

    protected $casts = [
        'detalles' => 'array',
        'fecha_hora' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Scope para filtrar por módulo
     */
    public function scopeModulo($query, ?string $modulo)
    {
        if ($modulo) {
            $query->where('modulo', $modulo);
        }
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeUsuario($query, ?int $usuarioId)
    {
        if ($usuarioId) {
            $query->where('usuario_id', $usuarioId);
        }
    }

    /**
     * Scope para filtrar por resultado (exitoso / fallido)
     */
    public function scopeResultado($query, ?string $resultado)
    {
        if ($resultado) {
            $query->where('resultado', $resultado);
        }
    }

    /**
     * Scope para búsqueda libre
     */
    public function scopeBuscar($query, ?string $termino)
    {
        if ($termino) {
            $query->where(function ($q) use ($termino) {
                $q->where('descripcion', 'like', "%{$termino}%")
                    ->orWhere('accion', 'like', "%{$termino}%")
                    ->orWhere('usuario_nombre', 'like', "%{$termino}%")
                    ->orWhere('modulo', 'like', "%{$termino}%")
                    ->orWhere('ip_address', 'like', "%{$termino}%");
            });
        }
    }
}
