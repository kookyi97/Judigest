<?php

namespace App\Services;

use App\Models\Usuario;

/** JD049: lógica del perfil propio. Siempre recibe el usuario autenticado, nunca un ID externo. */
class PerfilService
{
    public function __construct(private AuditoriaService $auditoria)
    {
    }

    /** Datos que se envían a la vista: lista explícita, nada de volcar el modelo completo. */
    public function datosPara(Usuario $usuario): array
    {
        return [
            'nombre'         => $usuario->nombre,
            'apellido'       => $usuario->apellido,
            'nombre_completo'=> trim("{$usuario->nombre} {$usuario->apellido}"),
            'telefono'       => $usuario->telefono,
            'nombre_usuario' => $usuario->nombre_usuario,
            'correo'         => $usuario->correo,
            'rol'            => $usuario->rol,
            'estado'         => $usuario->activo ? 'Activo' : 'Inactivo',
            'ultimo_acceso'  => $usuario->ultimo_acceso?->format('d/m/Y H:i'),
            'asesores'       => $usuario->rol === 'practicante'
                ? $usuario->asesoresAsignados()->map(fn (Usuario $a) => trim("{$a->nombre} {$a->apellido}"))->all()
                : null,
        ];
    }

    /**
     * Aplica los cambios y los audita.
     * @param  array<string,mixed> $datos  Ya validados.
     * @return list<string> Campos que realmente cambiaron.
     */
    public function actualizar(Usuario $usuario, array $datos): array
    {
        $usuario->fill(array_intersect_key($datos, array_flip(Usuario::CAMPOS_EDITABLES_PERFIL)));
        $cambiados = array_keys($usuario->getDirty());

        if ($cambiados === []) {
            return [];
        }

        $usuario->forceFill(['modificado_por' => $usuario->id])->save();

        $this->auditoria->registrar(
            modulo: 'Perfil',
            accion: 'Actualizar Perfil Propio',
            descripcion: "El usuario {$usuario->nombre} {$usuario->apellido} actualizó su perfil.",
            entidadTipo: Usuario::class,
            entidadId: $usuario->id,
            detalles: ['campos_modificados' => $cambiados], // solo nombres de campo, sin valores
        );

        return $cambiados;
    }

    /** Intento de ver o tocar el perfil de otra persona (JD003). Siempre termina en 403. */
    public function denegarAccesoAjeno(Usuario $usuario, string $referencia): never
    {
        $this->auditoria->registrar(
            modulo: 'Seguridad',
            accion: 'Intento de Acceso No Autorizado a Perfil Ajeno',
            descripcion: "El usuario {$usuario->nombre} {$usuario->apellido} intentó acceder al perfil de otro usuario.",
            entidadTipo: Usuario::class,
            entidadId: $usuario->id,
            detalles: ['referencia_solicitada' => mb_substr($referencia, 0, 50), 'metodo' => request()->method()],
            resultado: 'fallido',
        );

        abort(403, 'Acceso no autorizado: solo puedes ver y editar tu propio perfil.');
    }
}