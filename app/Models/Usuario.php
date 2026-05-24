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
        'nombre','apellido','correo','contrasena',
        'rol','activo','intentos_fallidos',
        'bloqueado_hasta','foto_perfil'
    ];

    protected $hidden = ['contrasena', 'remember_token'];

    protected $casts = [
        'contrasena'      => 'hashed',
        'bloqueado_hasta' => 'datetime',
        'activo'          => 'boolean',
    ];

    // Le dice a Laravel que el campo de contraseña es 'contrasena'
    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    // Le dice a Laravel que el campo username es 'correo'
    public function getAuthIdentifierName()
    {
        return 'correo';
    }

    public function estaBloqueado(): bool
    {
        if ($this->bloqueado_hasta && now()->lt($this->bloqueado_hasta)) {
            return true;
        }
        return false;
    }
}