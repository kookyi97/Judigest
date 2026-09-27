<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Expediente;
use App\Models\Usuario;
use Database\Seeders\ConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaConsultasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfiguracionSeeder::class);
    }

    public function test_consulta_bitacora_auditoria_es_registrada_automaticamente(): void
    {
        $admin = Usuario::create([
            'nombre_usuario' => 'admin_test',
            'nombre' => 'Admin',
            'apellido' => 'General',
            'correo' => 'admin@test.com',
            'contrasena' => 'password123',
            'rol' => 'administrador',
            'activo' => true,
        ]);

        $this->actingAs($admin)->get(route('auditoria.index', ['buscar' => 'test']));

        $this->assertDatabaseHas('auditorias', [
            'modulo' => 'Auditoría',
            'accion' => 'Consulta de Bitácora',
            'usuario_id' => $admin->id,
            'resultado' => 'exitoso',
        ]);
    }

    public function test_consulta_expedientes_es_registrada_automaticamente(): void
    {
        $secretario = Usuario::create([
            'nombre_usuario' => 'secretario_test',
            'nombre' => 'Secretario',
            'apellido' => 'Test',
            'correo' => 'sec@test.com',
            'contrasena' => 'password123',
            'rol' => 'secretario',
            'activo' => true,
        ]);

        $this->actingAs($secretario)->get(route('expedientes.index'));

        $this->assertDatabaseHas('auditorias', [
            'modulo' => 'Expedientes',
            'accion' => 'Consulta de Expedientes',
            'usuario_id' => $secretario->id,
            'resultado' => 'exitoso',
        ]);
    }
}
