<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'nombre'   => 'Admin',
                'apellido' => 'Principal',
                'correo'   => 'admin@sistema.com',
                'contrasena' => '12345',
                'rol'      => 'administrador',
            ],
            [
                'nombre'   => 'María',
                'apellido' => 'López',
                'correo'   => 'secretario@sistema.com',
                'contrasena' => '12345',
                'rol'      => 'secretario',
            ],
            [
                'nombre'   => 'Carlos',
                'apellido' => 'Ramírez',
                'correo'   => 'asesor@sistema.com',
                'contrasena' => '12345',
                'rol'      => 'asesor',
            ],
            [
                'nombre'   => 'Ana',
                'apellido' => 'González',
                'correo'   => 'practicante@sistema.com',
                'contrasena' => '12345',
                'rol'      => 'practicante',
            ],
        ];

        foreach ($usuarios as $u) {
            Usuario::create($u);
        }
    }
}