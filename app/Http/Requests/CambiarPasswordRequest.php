<?php

namespace App\Http\Requests;

use App\Rules\PoliticaPassword;
use App\Services\ConfiguracionService;
use Illuminate\Foundation\Http\FormRequest;

/** JD050: validación de FORMA y política. La contraseña actual se verifica en CambioPasswordService. */
class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Mínimo configurable por el administrador, pero nunca menor a 8.
        $minimo = max(8, (int) app(ConfiguracionService::class)->get('seguridad_longitud_min_password', 8));

        return [
            'password_actual' => ['required', 'string', 'max:255'],
            'password_nueva'  => ['required', 'string', 'confirmed', new PoliticaPassword($this->user(), $minimo)],
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required' => 'Ingresa tu contraseña actual.',
            'password_nueva.required'  => 'Ingresa la nueva contraseña.',
            'password_nueva.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
            'string'                   => 'El valor ingresado no es válido.',
            'max'                      => 'El valor ingresado es demasiado largo.',
        ];
    }
}