<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Usuario;
use Database\Seeders\ConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Valida los criterios de aceptación y escenarios Gherkin de JD051:
 * - Acceso controlado exclusivo para usuarios autenticados.
 * - Redirección al login para usuarios sin sesión activa.
 * - Disponibilidad y entrega dinámica del PDF correspondiente a cada rol (Administrador, Secretario, Asesor y Practicante).
 * - Registro en la bitácora de auditoría.
 */
class ManualUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private int $contador = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfiguracionSeeder::class);
    }

    private function crearUsuario(string $rol = 'practicante'): Usuario
    {
        $this->contador++;

        return Usuario::create([
            'nombre_usuario' => "user_{$rol}_{$this->contador}",
            'nombre'         => ucfirst($rol),
            'apellido'       => "Test {$this->contador}",
            'correo'         => "{$rol}{$this->contador}@test.com",
            'contrasena'     => 'password123',
            'rol'            => $rol,
            'activo'         => true,
        ]);
    }

    /**
     * Gherkin Scenario: Acceso al manual sin autenticación.
     */
    public function test_usuario_no_autenticado_es_redirigido_al_login(): void
    {
        $response = $this->get(route('manual.ver'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Gherkin Scenario: Acceso al manual con sesión iniciada (Visualización en PDF).
     */
    public function test_usuario_autenticado_puede_visualizar_el_manual_en_pdf(): void
    {
        $usuario = $this->crearUsuario('secretario');

        $response = $this->actingAs($usuario)->get(route('manual.ver'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition') ?? '');
        $this->assertStringContainsString('Manual_de_Usuario_Secretario_JUDIGEST.pdf', $response->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Gherkin Scenario: Cada uno de los 4 roles recibe de forma dinámica su manual específico.
     */
    public function test_cada_rol_autenticado_recibe_su_manual_especifico(): void
    {
        $roles = ['administrador', 'secretario', 'asesor', 'practicante'];

        foreach ($roles as $rol) {
            $usuario = $this->crearUsuario($rol);

            $response = $this->actingAs($usuario)->get(route('manual.ver'));
            $response->assertOk();
            $response->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringContainsString("Manual_de_Usuario_" . ucfirst($rol) . "_JUDIGEST.pdf", $response->headers->get('Content-Disposition') ?? '');
        }
    }

    /**
     * Descarga forzada del manual en PDF según el rol.
     */
    public function test_usuario_autenticado_puede_descargar_el_manual(): void
    {
        $usuario = $this->crearUsuario('asesor');

        $response = $this->actingAs($usuario)->get(route('manual.descargar'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
        $this->assertStringContainsString('Manual_de_Usuario_Asesor_JUDIGEST.pdf', $response->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Criterio de Auditoría: La consulta del manual queda registrada en la bitácora.
     */
    public function test_consulta_del_manual_queda_registrada_en_auditoria(): void
    {
        $usuario = $this->crearUsuario('administrador');

        $this->actingAs($usuario)->get(route('manual.ver'));

        $this->assertDatabaseHas('auditorias', [
            'modulo'      => 'Documentación',
            'accion'      => 'Consulta de Manual de Usuario',
            'usuario_id'  => $usuario->id,
            'resultado'   => 'exitoso',
        ]);
    }
}
