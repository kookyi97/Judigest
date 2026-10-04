<?php

namespace App\Services;

use App\Models\Audiencia;
use App\Models\Expediente;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Punto único para crear y consultar notificaciones (JD040 y JD041).
 *
 * Regla de entrega:
 *  - Notificaciones al PRACTICANTE por asignación: estrictas. Si no se pueden
 *    guardar lanzan excepción y la transacción que las contiene se revierte
 *    (así nunca queda un caso asignado sin aviso).
 *  - Notificaciones informativas (asesor, audiencias, estado, documentos):
 *    nunca interrumpen el flujo principal; si fallan se registra en el log.
 */
class NotificacionService
{
    public const TIPO_CASO_ASIGNADO     = 'caso_asignado';
    public const TIPO_CASO_REASIGNADO   = 'caso_reasignado';
    public const TIPO_CASO_RETIRADO     = 'caso_retirado';
    public const TIPO_PRACTICANTE       = 'practicante_asignado';
    public const TIPO_ESTADO            = 'estado_modificado';
    public const TIPO_DOCUMENTO         = 'documento_cargado';

    /* ------------------------------------------------------------------ */
    /*  Creación                                                          */
    /* ------------------------------------------------------------------ */

    /** Guarda la notificación. Lanza excepción si falla. */
    public function enviar(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?int $expedienteId = null,
        ?int $audienciaId = null
    ): Notificacion {
        return Notificacion::create([
            'usuario_id'    => $usuarioId,
            'tipo'          => $tipo,
            'titulo'        => $titulo,
            'mensaje'       => $mensaje,
            'expediente_id' => $expedienteId,
            'audiencia_id'  => $audienciaId,
            'leida'         => false,
            'estado_envio'  => 'enviada',
        ]);
    }

    /** Igual que enviar(), pero un fallo solo se registra en el log. */
    private function enviarSinInterrumpir(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?int $expedienteId = null,
        ?int $audienciaId = null
    ): void {
        try {
            $this->enviar($usuarioId, $tipo, $titulo, $mensaje, $expedienteId, $audienciaId);
        } catch (Throwable $e) {
            Log::error('No se pudo registrar la notificación.', [
                'usuario_id'    => $usuarioId,
                'tipo'          => $tipo,
                'expediente_id' => $expedienteId,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Eventos de negocio                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Se asignó o reasignó un practicante a un expediente.
     * - $anterior === null  → asignación nueva.
     * - $anterior !== null  → reasignación (se avisa también al que sale).
     * Al asesor se le informa siempre que el cambio no lo haya hecho él.
     */
    public function practicanteCambiado(
        Expediente $expediente,
        Usuario $nuevo,
        ?Usuario $anterior,
        Usuario $actor
    ): void {
        $numero = $expediente->numero_expediente;
        $quien  = $this->nombre($actor);

        if ($anterior === null) {
            $this->enviar(
                $nuevo->id,
                self::TIPO_CASO_ASIGNADO,
                'Nuevo caso asignado',
                "{$quien} te asignó el expediente {$numero} ({$expediente->tipo_proceso}).",
                $expediente->id
            );
        } else {
            $this->enviar(
                $nuevo->id,
                self::TIPO_CASO_REASIGNADO,
                'Caso reasignado a tu cargo',
                "{$quien} reasignó a tu cargo el expediente {$numero} ({$expediente->tipo_proceso}).",
                $expediente->id
            );
            $this->enviar(
                $anterior->id,
                self::TIPO_CASO_RETIRADO,
                'Caso reasignado a otro practicante',
                "El expediente {$numero} ya no está bajo tu cargo. Fue reasignado por {$quien}.",
                $expediente->id
            );
        }

        $detalle = $anterior === null
            ? "{$quien} asignó a {$this->nombre($nuevo)} al expediente {$numero}."
            : "{$quien} reasignó el expediente {$numero} de {$this->nombre($anterior)} a {$this->nombre($nuevo)}.";

        $this->avisarAsesor($expediente, $actor, self::TIPO_PRACTICANTE, 'Cambio de practicante en un caso', $detalle);
    }

    /** Audiencia registrada, modificada o cancelada: avisa a practicante y asesor. */
    public function audiencia(
        Expediente $expediente,
        Audiencia $audiencia,
        string $tipo,          // audiencia_registrada | audiencia_modificada
        string $titulo,
        string $mensaje,
        Usuario $actor
    ): void {
        if ($expediente->practicante_id) {
            $this->enviarSinInterrumpir(
                (int) $expediente->practicante_id, $tipo, $titulo, $mensaje, $expediente->id, $audiencia->id
            );
        }

        $this->avisarAsesor($expediente, $actor, $tipo, $titulo, $mensaje, $audiencia->id);
    }

    /** Cambio de estado (incluye archivado): avisa a asesor y practicante. */
    public function estadoModificado(Expediente $expediente, ?string $estadoAnterior, Usuario $actor): void
    {
        $mensaje = "{$this->nombre($actor)} cambió el estado del expediente {$expediente->numero_expediente} "
                 . "de '{$estadoAnterior}' a '{$expediente->estado}'.";

        if ($expediente->practicante_id && (int) $expediente->practicante_id !== (int) $actor->id) {
            $this->enviarSinInterrumpir(
                (int) $expediente->practicante_id, self::TIPO_ESTADO, 'Cambio de estado en tu caso', $mensaje, $expediente->id
            );
        }

        $this->avisarAsesor($expediente, $actor, self::TIPO_ESTADO, 'Cambio de estado en un caso', $mensaje);
    }

    /** Se cargó un documento: avisa al asesor responsable. */
    public function documentoCargado(Expediente $expediente, string $nombreDocumento, Usuario $actor): void
    {
        $this->avisarAsesor(
            $expediente,
            $actor,
            self::TIPO_DOCUMENTO,
            'Nuevo documento en un caso',
            "{$this->nombre($actor)} cargó el documento '{$nombreDocumento}' en el expediente {$expediente->numero_expediente}."
        );
    }

    /** Notifica al asesor del expediente, salvo que él mismo sea quien actuó. */
    private function avisarAsesor(
        Expediente $expediente,
        Usuario $actor,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?int $audienciaId = null
    ): void {
        if (!$expediente->asesor_id || (int) $expediente->asesor_id === (int) $actor->id) {
            return;
        }

        $this->enviarSinInterrumpir(
            (int) $expediente->asesor_id, $tipo, $titulo, $mensaje, $expediente->id, $audienciaId
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Consulta                                                          */
    /* ------------------------------------------------------------------ */

    public function noLeidas(Usuario $usuario): int
    {
        return Notificacion::where('usuario_id', $usuario->id)->where('leida', false)->count();
    }

    /** Últimas notificaciones para el dashboard. */
    public function recientes(Usuario $usuario, int $limite = 5): array
    {
        return Notificacion::where('usuario_id', $usuario->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limite)
            ->get()
            ->map(fn (Notificacion $n) => [
                'id'      => $n->id,
                'tipo'    => self::categoria($n->tipo),
                'titulo'  => $n->titulo,
                'mensaje' => $n->mensaje,
                'fecha'   => $n->created_at?->diffForHumans(),
                'leida'   => $n->leida,
            ])
            ->all();
    }

    /** Datos listos para la página de notificaciones. */
    public function formatear(Notificacion $n, Usuario $usuario): array
    {
        $puedeAbrir = $n->expediente && $n->expediente->esVisiblePara($usuario);

        return [
            'id'                => $n->id,
            'tipo'              => $n->tipo,
            'categoria'         => self::categoria($n->tipo),
            'titulo'            => $n->titulo,
            'mensaje'           => $n->mensaje,
            'leida'             => $n->leida,
            'estado_envio'      => $n->estado_envio,
            'fecha'             => $n->created_at?->format('d/m/Y H:i'),
            'fecha_relativa'    => $n->created_at?->diffForHumans(),
            'numero_expediente' => $n->expediente?->numero_expediente,
            // Solo se ofrece el enlace si el usuario aún tiene acceso al expediente.
            'url'               => $puedeAbrir ? "/expedientes/{$n->expediente_id}" : null,
        ];
    }

    /** Agrupa los tipos en las categorías que entiende el frontend. */
    public static function categoria(string $tipo): string
    {
        return match (true) {
            str_starts_with($tipo, 'audiencia') => 'audiencia',
            $tipo === self::TIPO_ESTADO         => 'estado',
            default                             => 'caso',
        };
    }

    private function nombre(Usuario $u): string
    {
        return trim("{$u->nombre} {$u->apellido}");
    }
}