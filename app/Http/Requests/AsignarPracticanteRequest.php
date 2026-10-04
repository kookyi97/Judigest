<?php

namespace App\Http\Requests;

use App\Models\Expediente;
use App\Services\AuditoriaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida y autoriza tanto la asignación (JD035) como la reasignación (JD036).
 * Solo puede operar el ASESOR RESPONSABLE de ese expediente.
 */
class AsignarPracticanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario    = $this->user();
        $expediente = $this->route('expediente');

        return $usuario?->rol === 'asesor'
            && $expediente instanceof Expediente
            && (int) $expediente->asesor_id === (int) $usuario->id;
    }

    public function rules(): array
    {
        return [
            'practicante_id' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id')->where(
                    fn ($q) => $q->where('rol', 'practicante')
                                 ->where('activo', true)
                                 ->whereNull('deleted_at')
                ),
            ],
        ];
    }

    public function messages(): array
    {
        $requerido = $this->isMethod('PUT')
            ? 'Debe seleccionar un nuevo practicante para reasignar el caso.'
            : 'Debe seleccionar un practicante para asignar al caso.';

        return [
            'practicante_id.required' => $requerido,
            'practicante_id.integer'  => 'El practicante seleccionado no es válido.',
            'practicante_id.exists'   => 'El practicante seleccionado no es válido o no está disponible.',
        ];
    }

    /** Todo intento fuera de permiso queda en la bitácora de seguridad. */
    protected function failedAuthorization(): void
    {
        $usuario    = $this->user();
        $expediente = $this->route('expediente');

        app(AuditoriaService::class)->registrar(
            modulo: 'Seguridad',
            accion: 'Intento de Acceso No Autorizado',
            descripcion: "El usuario {$usuario?->nombre} {$usuario?->apellido} (rol '{$usuario?->rol}') intentó "
                . 'asignar/reasignar un practicante en un expediente que no supervisa.',
            entidadTipo: Expediente::class,
            entidadId: $expediente instanceof Expediente ? $expediente->id : null,
            detalles: [
                'ruta'   => $this->path(),
                'metodo' => $this->method(),
            ],
            resultado: 'fallido',
            request: $this,
            usuario: $usuario
        );

        throw new AuthorizationException('No tiene autorización para gestionar este expediente.');
    }
}