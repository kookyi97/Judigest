<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * JD049: valida y sanea los datos editables del perfil propio.
 * Solo se leen nombre, apellido y teléfono; cualquier otro campo se ignora,
 * y si es un campo protegido se rechaza y se audita.
 */
class ActualizarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Sanitiza ANTES de validar: sin etiquetas HTML, espacios sobrantes ni saltos de línea. */
    protected function prepareForValidation(): void
    {
        $limpio = [];
        foreach (Usuario::CAMPOS_EDITABLES_PERFIL as $campo) {
            $valor = $this->input($campo);
            if (is_string($valor)) {
                $valor = trim(preg_replace('/\s+/u', ' ', strip_tags($valor)));
            }
            $limpio[$campo] = $valor;
        }
        $this->merge($limpio);
    }

    public function rules(): array
    {
        $nombre = ['required', 'string', 'min:2', 'max:100', "regex:/^[\\pL\\pM][\\pL\\pM .'\\-]*$/u"];

        return [
            'nombre'   => $nombre,
            'apellido' => $nombre,
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 ()\-]{6,18}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'       => 'El campo :attribute es obligatorio.',
            'string'         => 'El campo :attribute no tiene un formato válido.',
            'min'            => 'El campo :attribute debe tener al menos :min caracteres.',
            'max'            => 'El campo :attribute no puede superar los :max caracteres.',
            'nombre.regex'   => 'El nombre solo puede contener letras, espacios, apóstrofos y guiones.',
            'apellido.regex' => 'El apellido solo puede contener letras, espacios, apóstrofos y guiones.',
            'telefono.regex' => 'Ingresa un teléfono válido (solo números, espacios, guiones, paréntesis y + inicial).',
        ];
    }

    public function attributes(): array
    {
        return ['nombre' => 'nombre', 'apellido' => 'apellido', 'telefono' => 'teléfono'];
    }

    /** Detecta intentos de tocar campos protegidos (UI manipulada o petición armada a mano). */
    public function after(): array
    {
        return [function (Validator $validator) {
            $intentados = array_values(array_intersect(
                array_map('strtolower', array_keys($this->all())),
                Usuario::CAMPOS_PROTEGIDOS_PERFIL
            ));

            if ($intentados === []) {
                return;
            }

            // Sin $request explícito: AuditoriaService usa la petición global y evita el doble registro del middleware.
            app(AuditoriaService::class)->registrar(
                modulo: 'Seguridad',
                accion: 'Intento de Modificar Campos Protegidos del Perfil',
                descripcion: "El usuario {$this->user()->nombre} {$this->user()->apellido} intentó modificar campos protegidos de su perfil.",
                entidadTipo: Usuario::class,
                entidadId: $this->user()->id,
                detalles: ['campos_intentados' => $intentados], // solo nombres, nunca valores
                resultado: 'fallido',
            );

            $validator->errors()->add(
                'general',
                'Solicitud rechazada: solo puedes modificar tu nombre, apellido y teléfono.'
            );
        }];
    }
}