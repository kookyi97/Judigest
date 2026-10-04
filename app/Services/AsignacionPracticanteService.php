<?php

namespace App\Services;

use App\Models\Expediente;
use App\Models\Usuario;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de JD035 (asignar) y JD036 (reasignar).
 * Todo ocurre en UNA transacción: dato, historial, auditoría y notificación
 * se guardan juntos o no se guarda nada.
 */
class AsignacionPracticanteService
{
    public function __construct(
        private HistorialExpedienteService $historial,
        private AuditoriaService $auditoria,
        private NotificacionService $notificaciones,
    ) {
    }

    /** Practicantes activos con su carga actual. Solo expone id, nombre y apellido. */
    public function practicantesDisponibles(?int $excluirId = null): Collection
    {
        return Usuario::query()
            ->select(['id', 'nombre', 'apellido'])
            ->where('rol', 'practicante')
            ->where('activo', true)
            ->when($excluirId, fn ($q) => $q->where('id', '!=', $excluirId))
            ->withCount(['expedientesComoPracticante as casos_activos' => fn ($q) => $q->activos()])
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();
    }

    /** @throws DomainException|AuthorizationException */
    public function asignar(Expediente $expediente, int $practicanteId, Usuario $actor): void
    {
        $this->ejecutar($expediente, $practicanteId, $actor, esReasignacion: false);
    }

    /** @throws DomainException|AuthorizationException */
    public function reasignar(Expediente $expediente, int $practicanteId, Usuario $actor): void
    {
        $this->ejecutar($expediente, $practicanteId, $actor, esReasignacion: true);
    }

    private function ejecutar(Expediente $expediente, int $practicanteId, Usuario $actor, bool $esReasignacion): void
    {
        DB::transaction(function () use ($expediente, $practicanteId, $actor, $esReasignacion) {
            // Bloqueo: si dos asesores/pestañas operan a la vez, la segunda espera
            // y valida contra el dato ya actualizado.
            $exp = Expediente::query()->whereKey($expediente->id)->lockForUpdate()->firstOrFail();

            $this->validarReglas($exp, $practicanteId, $actor, $esReasignacion);

            $nuevo = Usuario::query()
                ->whereKey($practicanteId)
                ->where('rol', 'practicante')
                ->where('activo', true)
                ->first();

            if (!$nuevo) {
                throw new DomainException('El practicante seleccionado no es válido o no está disponible.');
            }

            $anterior = $exp->practicante_id ? Usuario::withTrashed()->find($exp->practicante_id) : null;

            $exp->update([
                'practicante_id' => $nuevo->id,
                'modificado_por' => $actor->id,
            ]);

            $this->registrarTrazas($exp, $nuevo, $anterior, $actor, $esReasignacion);

            $this->notificaciones->practicanteCambiado($exp, $nuevo, $anterior, $actor);
        });
    }

    private function validarReglas(Expediente $exp, int $practicanteId, Usuario $actor, bool $esReasignacion): void
    {
        // Defensa en profundidad: aunque el FormRequest ya lo comprobó.
        if ($actor->rol !== 'asesor' || (int) $exp->asesor_id !== (int) $actor->id) {
            throw new AuthorizationException('No tiene autorización para gestionar este expediente.');
        }

        if (in_array($exp->estado, Expediente::ESTADOS_FINALES, true)) {
            throw new DomainException('No se puede cambiar el practicante de un expediente cerrado o archivado.');
        }

        if (!$esReasignacion && $exp->practicante_id !== null) {
            throw new DomainException('El expediente ya tiene un practicante asignado. Use la opción Reasignar.');
        }

        if ($esReasignacion && $exp->practicante_id === null) {
            throw new DomainException('El expediente no tiene practicante asignado. Use la opción Asignar.');
        }

        if ($esReasignacion && (int) $exp->practicante_id === $practicanteId) {
            throw new DomainException('El nuevo practicante debe ser distinto al practicante actual.');
        }
    }

    private function registrarTrazas(
        Expediente $exp,
        Usuario $nuevo,
        ?Usuario $anterior,
        Usuario $actor,
        bool $esReasignacion
    ): void {
        $cuando   = now();
        $realizador = trim("{$actor->nombre} {$actor->apellido}");
        $nombreNuevo = trim("{$nuevo->nombre} {$nuevo->apellido}");
        $nombreAnterior = $anterior ? trim("{$anterior->nombre} {$anterior->apellido}") : 'Sin asignar';

        $descripcion = $esReasignacion
            ? "Se reasignó el caso de {$nombreAnterior} a {$nombreNuevo}."
            : "Se asignó el practicante {$nombreNuevo} al caso.";

        $this->historial->registrar(
            $exp->id,
            $esReasignacion ? 'REASIGNACION_PRACTICANTE' : 'ASIGNACION_PRACTICANTE',
            $descripcion,
            [
                'Practicante anterior' => $nombreAnterior,
                'Practicante nuevo'    => $nombreNuevo,
                'Realizado por'        => $realizador,
                'Fecha y hora'         => $cuando->format('d/m/Y H:i:s'),
            ]
        );

        $this->auditoria->registrar(
            modulo: 'Expedientes',
            accion: $esReasignacion ? 'Reasignar Practicante' : 'Asignar Practicante',
            descripcion: "{$realizador} " . ($esReasignacion ? 'reasignó' : 'asignó')
                . " el expediente {$exp->numero_expediente}: {$nombreAnterior} → {$nombreNuevo}.",
            entidadTipo: Expediente::class,
            entidadId: $exp->id,
            detalles: [
                'expediente_id'         => $exp->id,
                'numero_expediente'     => $exp->numero_expediente,
                'practicante_anterior'  => $anterior?->id,
                'practicante_nuevo'     => $nuevo->id,
                'fecha_hora'            => $cuando->toIso8601String(),
            ],
            usuario: $actor
        );
    }
}