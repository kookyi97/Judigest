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
     * Scope para filtrar por tipo de acción
     */
    public function scopeAccion($query, ?string $accion)
    {
        if ($accion) {
            $query->where('accion', $accion);
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
     * Scope para filtrar por hora desde (HH:MM o HH:MM:SS)
     */
    public function scopeHoraDesde($query, ?string $hora)
    {
        if ($hora) {
            $query->whereTime('fecha_hora', '>=', $hora);
        }
    }

    /**
     * Scope para filtrar por hora hasta (HH:MM o HH:MM:SS)
     */
    public function scopeHoraHasta($query, ?string $hora)
    {
        if ($hora) {
            $query->whereTime('fecha_hora', '<=', $hora);
        }
    }

    /**
     * Scope para filtrar solo actividades sospechosas o críticas
     */
    public function scopeSoloSospechosas($query, bool $activar = true)
    {
        if ($activar) {
            $query->where(function ($q) {
                $q->where('resultado', 'fallido')
                    ->orWhere('accion', 'like', '%Eliminar%')
                    ->orWhere('accion', 'like', '%Bloqueo%')
                    ->orWhere('accion', 'like', '%Restablecer Contraseña%')
                    ->orWhere('accion', 'like', '%Modificar Rol%')
                    ->orWhere('accion', 'like', '%No Autorizado%')
                    ->orWhere('modulo', 'Seguridad');
            });
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

    /**
     * Determina si este registro clasifica como actividad sospechosa o crítica.
     */
    public function esCriticaOSospechosa(): bool
    {
        if ($this->resultado === 'fallido') {
            return true;
        }

        $accion = mb_strtolower($this->accion ?? '');
        $modulo = mb_strtolower($this->modulo ?? '');

        if ($modulo === 'seguridad') {
            return true;
        }

        $palabrasCriticas = [
            'eliminar',
            'bloqueo',
            'restablecer contraseña',
            'modificar rol',
            'no autorizado',
            'anomalía',
            'forzado',
        ];

        foreach ($palabrasCriticas as $palabra) {
            if (str_contains($accion, $palabra)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Explica brevemente la razón por la cual se considera sospechosa o crítica.
     */
    public function motivoSospecha(): ?string
    {
        if (!$this->esCriticaOSospechosa()) {
            return null;
        }

        if ($this->resultado === 'fallido') {
            return 'Operación fallida / Posible intento no autorizado o anomalía';
        }

        $accion = mb_strtolower($this->accion ?? '');
        if (str_contains($accion, 'eliminar')) {
            return 'Acción de alto impacto: Eliminación permanente de información';
        }
        if (str_contains($accion, 'modificar rol') || str_contains($accion, 'rol')) {
            return 'Alteración sensible de privilegios de usuario';
        }
        if (str_contains($accion, 'restablecer contraseña')) {
            return 'Restablecimiento de credenciales de seguridad';
        }
        if (str_contains($accion, 'bloqueo')) {
            return 'Bloqueo preventivo por exceso de intentos erróneos';
        }

        return 'Actividad sensible de administración o seguridad';
    }
}
