<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Expediente;
use App\Models\Configuracion;
use App\Services\ConfiguracionService;
use Database\Seeders\ConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

class ExpedienteConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfiguracionSeeder::class);
    }

    public function test_create_view_receives_configured_tipos_proceso(): void
    {
        $usuario = Usuario::create([
            'nombre_usuario' => 'secretario1',
            'nombre' => 'Secretario',
            'apellido' => 'Test',
            'correo' => 'secretario@test.com',
            'contrasena' => 'password123',
            'rol' => 'secretario',
            'activo' => true,
        ]);

        // Actualizamos la configuración para incluir Comercial
        app(ConfiguracionService::class)->actualizarParametros([
            'expedientes_tipos_proceso' => ['Civil', 'Penal', 'Laboral', 'Familia', 'Administrativo', 'Comercial']
        ], $usuario->id);

        $response = $this->actingAs($usuario)->get(route('expedientes.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Expedientes/Create')
            ->has('tiposProceso')
            ->whereContains('tiposProceso', 'Comercial')
        );
    }

    public function test_can_create_expediente_with_comercial_type(): void
    {
        $secretario = Usuario::create([
            'nombre_usuario' => 'secretario2',
            'nombre' => 'Secretario',
            'apellido' => 'Dos',
            'correo' => 'secretario2@test.com',
            'contrasena' => 'password123',
            'rol' => 'secretario',
            'activo' => true,
        ]);

        $asesor = Usuario::create([
            'nombre_usuario' => 'asesor1',
            'nombre' => 'Asesor',
            'apellido' => 'Juridico',
            'correo' => 'asesor1@test.com',
            'contrasena' => 'password123',
            'rol' => 'asesor',
            'activo' => true,
        ]);

        // Actualizamos la configuración para incluir Comercial
        app(ConfiguracionService::class)->actualizarParametros([
            'expedientes_tipos_proceso' => ['Civil', 'Penal', 'Laboral', 'Familia', 'Administrativo', 'Comercial']
        ], $secretario->id);

        $response = $this->actingAs($secretario)->post(route('expedientes.store'), [
            'cliente' => 'Empresa Comercial Test S.A.S.',
            'tipo_proceso' => 'Comercial',
            'asesor_id' => $asesor->id,
            'fecha_ingreso' => now()->toDateString(),
            'estado' => 'Abierto',
            'descripcion' => 'Prueba de expediente comercial',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('expedientes.index'));

        $this->assertDatabaseHas('expedientes', [
            'cliente' => 'Empresa Comercial Test S.A.S.',
            'tipo_proceso' => 'Comercial',
        ]);
    }

    public function test_cannot_create_expediente_with_disabled_type(): void
    {
        $secretario = Usuario::create([
            'nombre_usuario' => 'secretario3',
            'nombre' => 'Secretario',
            'apellido' => 'Tres',
            'correo' => 'secretario3@test.com',
            'contrasena' => 'password123',
            'rol' => 'secretario',
            'activo' => true,
        ]);

        $asesor = Usuario::create([
            'nombre_usuario' => 'asesor2',
            'nombre' => 'Asesor',
            'apellido' => 'Dos',
            'correo' => 'asesor2@test.com',
            'contrasena' => 'password123',
            'rol' => 'asesor',
            'activo' => true,
        ]);

        $response = $this->actingAs($secretario)->post(route('expedientes.store'), [
            'cliente' => 'Cliente Tipo Inválido',
            'tipo_proceso' => 'TipoInexistente999',
            'asesor_id' => $asesor->id,
            'fecha_ingreso' => now()->toDateString(),
            'estado' => 'Abierto',
        ]);

        $response->assertSessionHasErrors('tipo_proceso');
    }

    public function test_user_creation_validates_configured_password_length(): void
    {
        $admin = Usuario::create([
            'nombre_usuario' => 'admin_test',
            'nombre' => 'Admin',
            'apellido' => 'Test',
            'correo' => 'admin@test.com',
            'contrasena' => 'password123',
            'rol' => 'administrador',
            'activo' => true,
        ]);

        // Configuramos longitud mínima de 10 caracteres
        app(ConfiguracionService::class)->actualizarParametros([
            'seguridad_longitud_min_password' => 10
        ], $admin->id);

        // Intento con 8 caracteres (válido antes, inválido con la nueva config de 10)
        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'nombre_usuario' => 'practicante_8chars',
            'nombre' => 'Practicante',
            'apellido' => 'Prueba',
            'correo' => 'practicante8@test.com',
            'contrasena' => '12345678',
            'contrasena_confirmation' => '12345678',
            'rol' => 'practicante',
            'activo' => true,
        ]);

        $response->assertSessionHasErrors('contrasena');

        // Intento con 10 caracteres (válido)
        $responseValido = $this->actingAs($admin)->post(route('usuarios.store'), [
            'nombre_usuario' => 'practicante_10chars',
            'nombre' => 'Practicante',
            'apellido' => 'Diez',
            'correo' => 'practicante10@test.com',
            'contrasena' => '1234567890',
            'contrasena_confirmation' => '1234567890',
            'rol' => 'practicante',
            'activo' => true,
        ]);

        $responseValido->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuarios', ['nombre_usuario' => 'practicante_10chars']);
    }

    public function test_index_marks_inactivity_based_on_configured_days(): void
    {
        $secretario = Usuario::create([
            'nombre_usuario' => 'secretario_inac',
            'nombre' => 'Sec',
            'apellido' => 'Inac',
            'correo' => 'secinac@test.com',
            'contrasena' => 'password123',
            'rol' => 'secretario',
            'activo' => true,
        ]);

        $asesor = Usuario::create([
            'nombre_usuario' => 'asesor_inac',
            'nombre' => 'Ase',
            'apellido' => 'Inac',
            'correo' => 'aseinac@test.com',
            'contrasena' => 'password123',
            'rol' => 'asesor',
            'activo' => true,
        ]);

        // Configuramos 10 días de inactividad para alerta
        app(ConfiguracionService::class)->actualizarParametros([
            'expedientes_dias_alerta_inactividad' => 10
        ], $secretario->id);

        $expInactivo = Expediente::create([
            'numero_expediente' => 'EXP-2026-9001',
            'cliente' => 'Caso Inactivo Test',
            'tipo_proceso' => 'Civil',
            'asesor_id' => $asesor->id,
            'fecha_ingreso' => now()->subDays(20)->toDateString(),
            'estado' => 'Abierto',
            'creado_por' => $secretario->id,
        ]);
        $expInactivo->updated_at = now()->subDays(15);
        $expInactivo->saveQuietly();

        $response = $this->actingAs($secretario)->get(route('expedientes.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Expedientes/Index')
            ->where('diasAlertaInactividad', 10)
            ->where('expedientes.0.alerta_inactividad', true)
        );
    }

    public function test_can_archivar_expediente_without_errors(): void
    {
        $secretario = Usuario::create([
            'nombre_usuario' => 'secretario_arch',
            'nombre' => 'Sec',
            'apellido' => 'Arch',
            'correo' => 'secarch@test.com',
            'contrasena' => 'password123',
            'rol' => 'secretario',
            'activo' => true,
        ]);

        $asesor = Usuario::create([
            'nombre_usuario' => 'asesor_arch',
            'nombre' => 'Ase',
            'apellido' => 'Arch',
            'correo' => 'asearch@test.com',
            'contrasena' => 'password123',
            'rol' => 'asesor',
            'activo' => true,
        ]);

        $expediente = Expediente::create([
            'numero_expediente' => 'EXP-2026-8888',
            'cliente' => 'Cliente Archivar',
            'tipo_proceso' => 'Civil',
            'asesor_id' => $asesor->id,
            'fecha_ingreso' => now()->toDateString(),
            'estado' => 'Abierto',
            'creado_por' => $secretario->id,
        ]);

        $response = $this->actingAs($secretario)->put(route('expedientes.archivar', $expediente));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('expedientes', [
            'id' => $expediente->id,
            'estado' => 'Archivado',
        ]);
    }

    public function test_actualizar_parametros_registra_en_bitacora_central_de_auditoria(): void
    {
        $admin = Usuario::create([
            'nombre_usuario' => 'admin_config',
            'nombre' => 'Admin',
            'apellido' => 'Config',
            'correo' => 'adminconfig@test.com',
            'contrasena' => 'password123',
            'rol' => 'administrador',
            'activo' => true,
        ]);

        app(ConfiguracionService::class)->actualizarParametros([
            'expedientes_prefijo' => 'CASO',
        ], $admin->id);

        $this->assertDatabaseHas('historial_configuraciones', [
            'parametro_clave' => 'expedientes_prefijo',
            'valor_nuevo' => 'CASO',
            'usuario_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('auditorias', [
            'modulo' => 'Configuración',
            'accion' => 'Actualizar Parámetro',
            'usuario_id' => $admin->id,
            'resultado' => 'exitoso',
        ]);
    }
}

