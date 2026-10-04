<?php

namespace Tests\Feature;

use App\Models\Expediente;
use App\Models\Notificacion;
use App\Models\Usuario;
use Database\Seeders\ConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Cubre JD033, JD035, JD036, JD038, JD040 y JD041 (visibilidad, asignación,
 * reasignación y notificaciones), con foco en los casos de seguridad.
 */
class AsignacionYVisibilidadTest extends TestCase
{
    use RefreshDatabase;

    private int $contador = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfiguracionSeeder::class);
    }

    private function usuario(string $rol): Usuario
    {
        $this->contador++;

        return Usuario::create([
            'nombre_usuario' => "{$rol}{$this->contador}",
            'nombre'         => ucfirst($rol),
            'apellido'       => "N{$this->contador}",
            'correo'         => "{$rol}{$this->contador}@test.com",
            'contrasena'     => 'password123',
            'rol'            => $rol,
            'activo'         => true,
        ]);
    }

    private function expediente(Usuario $asesor, ?Usuario $practicante = null, array $extra = []): Expediente
    {
        $this->contador++;

        return Expediente::create(array_merge([
            'numero_expediente' => sprintf('EXP-2026-%04d', $this->contador),
            'cliente'           => 'Cliente de prueba',
            'tipo_proceso'      => 'Civil',
            'asesor_id'         => $asesor->id,
            'practicante_id'    => $practicante?->id,
            'fecha_ingreso'     => now()->toDateString(),
            'creado_por'        => $asesor->id,
            'estado'            => 'Abierto',
        ], $extra));
    }

    /* ---------------------------- JD033 ---------------------------- */

    public function test_asesor_solo_ve_los_expedientes_bajo_su_supervision(): void
    {
        $asesorA = $this->usuario('asesor');
        $asesorB = $this->usuario('asesor');
        $propio  = $this->expediente($asesorA);
        $this->expediente($asesorB);

        $this->actingAs($asesorA)
            ->get(route('expedientes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expedientes/Index')
                ->has('expedientes', 1)
                ->where('expedientes.0.id', $propio->id)
                ->where('expedientes.0.avance', 10)
            );
    }

    /* ---------------------------- JD038 ---------------------------- */

    public function test_practicante_solo_ve_sus_expedientes_y_no_puede_abrir_los_ajenos(): void
    {
        $asesor = $this->usuario('asesor');
        $pracA  = $this->usuario('practicante');
        $pracB  = $this->usuario('practicante');
        $propio = $this->expediente($asesor, $pracA);
        $ajeno  = $this->expediente($asesor, $pracB);

        $this->actingAs($pracA)
            ->get(route('expedientes.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('expedientes', 1)
                ->where('expedientes.0.id', $propio->id)
            );

        $this->actingAs($pracA)->get(route('expedientes.show', $ajeno))->assertForbidden();
    }

    /* ---------------------------- JD035 ---------------------------- */

    public function test_asesor_asigna_practicante_y_se_registra_y_notifica(): void
    {
        $asesor = $this->usuario('asesor');
        $prac   = $this->usuario('practicante');
        $exp    = $this->expediente($asesor);

        $this->actingAs($asesor)
            ->post(route('expedientes.practicante.asignar', $exp), ['practicante_id' => $prac->id])
            ->assertRedirect()
            ->assertSessionHas('exito');

        $this->assertSame($prac->id, $exp->fresh()->practicante_id);

        $this->assertDatabaseHas('notificaciones', [
            'usuario_id'    => $prac->id,
            'tipo'          => 'caso_asignado',
            'expediente_id' => $exp->id,
            'leida'         => false,
        ]);

        $this->assertDatabaseHas('historial_expedientes', [
            'expediente_id' => $exp->id,
            'usuario_id'    => $asesor->id,
            'accion'        => 'ASIGNACION_PRACTICANTE',
        ]);

        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $asesor->id,
            'accion'     => 'Asignar Practicante',
        ]);
    }

    public function test_asesor_no_puede_asignar_en_un_expediente_de_otro_asesor(): void
    {
        $asesor = $this->usuario('asesor');
        $otro   = $this->usuario('asesor');
        $prac   = $this->usuario('practicante');
        $ajeno  = $this->expediente($otro);

        $this->actingAs($asesor)
            ->post(route('expedientes.practicante.asignar', $ajeno), ['practicante_id' => $prac->id])
            ->assertForbidden();

        $this->assertNull($ajeno->fresh()->practicante_id);
    }

    public function test_otros_roles_no_pueden_usar_la_ruta_de_asignacion(): void
    {
        $asesor     = $this->usuario('asesor');
        $secretario = $this->usuario('secretario');
        $prac       = $this->usuario('practicante');
        $exp        = $this->expediente($asesor);

        $this->actingAs($secretario)
            ->post(route('expedientes.practicante.asignar', $exp), ['practicante_id' => $prac->id])
            ->assertForbidden();

        $this->actingAs($prac)
            ->post(route('expedientes.practicante.asignar', $exp), ['practicante_id' => $prac->id])
            ->assertForbidden();
    }

    public function test_no_se_puede_asignar_un_usuario_que_no_es_practicante(): void
    {
        $asesor = $this->usuario('asesor');
        $otro   = $this->usuario('secretario');
        $exp    = $this->expediente($asesor);

        $this->actingAs($asesor)
            ->post(route('expedientes.practicante.asignar', $exp), ['practicante_id' => $otro->id])
            ->assertSessionHasErrors('practicante_id');

        $this->assertNull($exp->fresh()->practicante_id);
    }

    /* ---------------------------- JD036 ---------------------------- */

    public function test_reasignar_sin_seleccionar_practicante_muestra_validacion(): void
    {
        $asesor = $this->usuario('asesor');
        $prac   = $this->usuario('practicante');
        $exp    = $this->expediente($asesor, $prac);

        $this->actingAs($asesor)
            ->put(route('expedientes.practicante.reasignar', $exp), [])
            ->assertSessionHasErrors('practicante_id');

        $this->assertSame($prac->id, $exp->fresh()->practicante_id);
    }

    public function test_reasignar_notifica_al_nuevo_y_al_anterior(): void
    {
        $asesor = $this->usuario('asesor');
        $viejo  = $this->usuario('practicante');
        $nuevo  = $this->usuario('practicante');
        $exp    = $this->expediente($asesor, $viejo);

        $this->actingAs($asesor)
            ->put(route('expedientes.practicante.reasignar', $exp), ['practicante_id' => $nuevo->id])
            ->assertSessionHas('exito');

        $this->assertSame($nuevo->id, $exp->fresh()->practicante_id);
        $this->assertDatabaseHas('notificaciones', ['usuario_id' => $nuevo->id, 'tipo' => 'caso_reasignado']);
        $this->assertDatabaseHas('notificaciones', ['usuario_id' => $viejo->id, 'tipo' => 'caso_retirado']);
        $this->assertDatabaseHas('historial_expedientes', ['expediente_id' => $exp->id, 'accion' => 'REASIGNACION_PRACTICANTE']);

        // El practicante anterior pierde el acceso de inmediato.
        $this->actingAs($viejo)->get(route('expedientes.show', $exp))->assertForbidden();
    }

    public function test_no_se_puede_reasignar_al_mismo_practicante(): void
    {
        $asesor = $this->usuario('asesor');
        $prac   = $this->usuario('practicante');
        $exp    = $this->expediente($asesor, $prac);

        $this->actingAs($asesor)
            ->put(route('expedientes.practicante.reasignar', $exp), ['practicante_id' => $prac->id])
            ->assertSessionHasErrors('practicante_id');
    }

    public function test_no_se_puede_cambiar_practicante_en_expediente_cerrado(): void
    {
        $asesor = $this->usuario('asesor');
        $prac   = $this->usuario('practicante');
        $exp    = $this->expediente($asesor, null, ['estado' => 'Cerrado']);

        $this->actingAs($asesor)
            ->post(route('expedientes.practicante.asignar', $exp), ['practicante_id' => $prac->id])
            ->assertSessionHasErrors('practicante_id');

        $this->assertNull($exp->fresh()->practicante_id);
    }

    /* ------------------------ JD040 / JD041 ------------------------ */

    public function test_usuario_solo_puede_marcar_sus_propias_notificaciones(): void
    {
        $prac  = $this->usuario('practicante');
        $otro  = $this->usuario('practicante');
        $ajena = Notificacion::create([
            'usuario_id' => $otro->id, 'tipo' => 'caso_asignado',
            'titulo' => 'x', 'mensaje' => 'y', 'leida' => false, 'estado_envio' => 'enviada',
        ]);

        $this->actingAs($prac)
            ->patch(route('notificaciones.leida', $ajena->id))
            ->assertNotFound();

        $this->assertFalse($ajena->fresh()->leida);
    }

    public function test_asesor_recibe_aviso_cuando_el_secretario_cambia_el_estado(): void
    {
        $asesor     = $this->usuario('asesor');
        $secretario = $this->usuario('secretario');
        $exp        = $this->expediente($asesor);

        $this->actingAs($secretario)
            ->put(route('expedientes.estado', $exp), ['estado' => 'En Proceso'])
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('notificaciones', [
            'usuario_id' => $asesor->id,
            'tipo'       => 'estado_modificado',
        ]);
    }

    public function test_la_pagina_de_notificaciones_solo_lista_las_propias(): void
    {
        $asesor = $this->usuario('asesor');
        $prac   = $this->usuario('practicante');
        $exp    = $this->expediente($asesor);

        $this->actingAs($asesor)->post(
            route('expedientes.practicante.asignar', $exp),
            ['practicante_id' => $prac->id]
        );

        $this->actingAs($prac)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Notificaciones/Index')
                ->where('noLeidas', 1)
                ->has('notificaciones.data', 1)
            );

        $this->actingAs($this->usuario('secretario'))
            ->get(route('notificaciones.index'))
            ->assertForbidden();
    }
}