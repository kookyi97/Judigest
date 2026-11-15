<?php

namespace App\Rules;

use App\Models\Usuario;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * JD050: política de contraseñas.
 * No compara contra la contraseña actual: eso lo hace el servicio DESPUÉS de
 * verificar la actual, para que este formulario no sirva de oráculo.
 */
class PoliticaPassword implements ValidationRule
{
    /** Límite real de bcrypt: lo que pase de 72 bytes se ignora al hashear. */
    private const MAX_BYTES = 72;

    private const COMUNES = [
        'password', 'contrasena', 'contrasenia', 'clave', 'admin', 'administrador', 'administrator',
        'qwerty', 'qwertyuiop', 'abc', 'abcd', 'abcdef', 'letmein', 'welcome', 'bienvenido',
        'iloveyou', 'monkey', 'dragon', 'master', 'login', 'secreto', 'secret', 'changeme',
        'judigest', 'sistemajd', 'consultorio', 'juridico', 'sistema', 'usuario', 'practicante',
        'asesor', 'secretario', 'abuelo', 'futbol', 'football',
    ];

    public function __construct(
        private Usuario $usuario,
        private int $longitudMinima = 8,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;

        if (mb_strlen($password) < $this->longitudMinima) {
            $fail("La contraseña debe tener al menos {$this->longitudMinima} caracteres.");
        }
        if (strlen($password) > self::MAX_BYTES) {
            $fail('La contraseña no puede superar los 72 caracteres.');
        }
        if (!preg_match('/\p{Lu}/u', $password)) {
            $fail('La contraseña debe incluir al menos una letra mayúscula.');
        }
        if (!preg_match('/\p{Ll}/u', $password)) {
            $fail('La contraseña debe incluir al menos una letra minúscula.');
        }
        if (!preg_match('/\d/', $password)) {
            $fail('La contraseña debe incluir al menos un número.');
        }
        if (!preg_match('/[^\p{L}\p{N}\s]/u', $password)) {
            $fail('La contraseña debe incluir al menos un símbolo (por ejemplo ! @ # $ %).');
        }
        if ($this->esComun($password)) {
            $fail('La contraseña es demasiado común. Elige una más difícil de adivinar.');
        }
        if ($this->contieneDatosPersonales($password)) {
            $fail('La contraseña no puede contener tu correo ni tu nombre de usuario.');
        }
    }

    private function esComun(string $password): bool
    {
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $this->sinTildes($password)));
        $sinNumeros = rtrim($base, '0123456789');

        return in_array($base, self::COMUNES, true)
            || in_array($sinNumeros, self::COMUNES, true)
            || preg_match('/^(?:0?123456789?0?|1234567890|987654321)$/', $base) === 1;
    }

    private function contieneDatosPersonales(string $password): bool
    {
        $texto = mb_strtolower($password);
        $candidatos = [
            strstr((string) $this->usuario->correo, '@', true) ?: '',
            (string) $this->usuario->correo,
            (string) $this->usuario->nombre_usuario,
        ];

        foreach ($candidatos as $dato) {
            $dato = mb_strtolower(trim($dato));
            if (mb_strlen($dato) >= 3 && str_contains($texto, $dato)) {
                return true;
            }
        }

        return false;
    }

    private function sinTildes(string $texto): string
    {
        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
                              'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
    }
}