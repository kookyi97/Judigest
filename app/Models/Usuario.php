<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usuario extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre_usuario', 'nombre','apellido','correo','telefono','contrasena',
        'rol','activo','intentos_fallidos',
        'bloqueado_hasta','foto_perfil', 'modificado_por'
    ];

    /**
     * JD049: lo ÚNICO que el usuario puede editar de su propio perfil.
     * $fillable incluye rol/activo porque el Administrador sí los gestiona (JD004/JD008),
     * por eso el perfil NO usa $fillable como defensa: usa esta lista blanca.
     */
    public const CAMPOS_EDITABLES_PERFIL = ['nombre', 'apellido', 'telefono'];

    /** JD049: campos que, si llegan en la petición del perfil, se consideran un intento de manipulación. */
    public const CAMPOS_PROTEGIDOS_PERFIL = [
        'id', 'rol', 'activo', 'estado', 'correo', 'nombre_usuario', 'contrasena', 'password',
        'intentos_fallidos', 'bloqueado_hasta', 'ultimo_acceso', 'modificado_por',
        'foto_perfil', 'remember_token', 'deleted_at', 'created_at', 'updated_at',
    ];

    protected $hidden = ['contrasena', 'remember_token'];

    protected $casts = [
        'contrasena'      => 'hashed',
        'bloqueado_hasta' => 'datetime',
        'ultimo_acceso'   => 'datetime',
        'activo'          => 'boolean',
    ];

    // Le dice a Laravel que el campo de contraseña es 'contrasena'
    public function getAuthPassword()
    {
        return $this->contrasena;
    }


    public function estaBloqueado(): bool
    {
        if ($this->bloqueado_hasta && now()->lt($this->bloqueado_hasta)) {
            return true;
        }
        return false;
    }

    /** JD001/JD045/JD049: login correcto → limpia bloqueos y marca el último acceso (solo el servidor lo escribe). */
    public function registrarAccesoExitoso(): void
    {
        $this->forceFill([
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
            'ultimo_acceso'     => now(),
        ])->save();
    }

    /** JD049: asesores de los expedientes activos de un practicante (sin repetir). */
    public function asesoresAsignados()
    {
        return self::query()
            ->whereIn('id', Expediente::query()
                ->where('practicante_id', $this->id)
                ->whereNotIn('estado', Expediente::ESTADOS_FINALES)
                ->whereNotNull('asesor_id')
                ->select('asesor_id'))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'apellido']);
    }

    public function modificador()
    {
        return $this->belongsTo(Usuario::class, 'modificado_por');
    }

    public function expedientesComoPracticante()
    {
        return $this->hasMany(Expediente::class, 'practicante_id');
    }
}