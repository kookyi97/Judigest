<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** JD050: cambio de contraseña propia (verificación, bloqueo temporal JD045, cierre de otras sesiones). */
class CambioPasswordService
{
    private const MINUTOS_BLOQUEO = 15;

    public function __construct(
        private AuditoriaService $auditoria,
        private NotificacionService $notificaciones,
        private ConfiguracionService $configuracion,
    ) {
    }

    /** @throws ValidationException */
    public function cambiar(Usuario $usuario, string $actual, string $nueva, Request $request): void
    {
        $clave = "cambio-password:{$usuario->id}";
        $maximo = (int) $this->configuracion->get('seguridad_max_intentos_login', 5);

        // 1) ¿Operación bloqueada temporalmente?
        if (RateLimiter::tooManyAttempts($clave, $maximo)) {
            $minutos = (int) ceil(RateLimiter::availableIn($clave) / 60);
            throw ValidationException::withMessages([
                'password_actual' => "Demasiados intentos. Inténtalo de nuevo en {$minutos} minuto(s).",
            ]);
        }

        // 2) Contraseña actual (mensaje genérico: no dice nada más)
        if (!Hash::check($actual, $usuario->contrasena)) {
            RateLimiter::hit($clave, self::MINUTOS_BLOQUEO * 60);
            $this->registrarFallo($usuario, RateLimiter::tooManyAttempts($clave, $maximo));

            throw ValidationException::withMessages([
                'password_actual' => 'La contraseña actual no es correcta.',
            ]);
        }

        // 3) Solo ahora (ya autenticado el intento) se compara la nueva con la actual
        if (Hash::check($nueva, $usuario->contrasena)) {
            throw ValidationException::withMessages([
                'password_nueva' => 'La nueva contraseña debe ser diferente de la actual.',
            ]);
        }

        // 4) Guardar (el cast 'hashed' aplica bcrypt, JD044) y rotar el remember_token
        $usuario->forceFill([
            'contrasena'     => $nueva,
            'remember_token' => Str::random(60),
            'modificado_por' => $usuario->id,
        ])->save();

        RateLimiter::clear($clave);

        // 5) Nuevo ID de sesión (destruye el anterior) y cierre de las demás sesiones
        $request->session()->regenerate(true);
        $this->cerrarOtrasSesiones($usuario, $request->session()->getId());

        $this->auditoria->registrar(
            modulo: 'Seguridad',
            accion: 'Cambio de Contraseña Propia',
            descripcion: "El usuario {$usuario->nombre} {$usuario->apellido} cambió su contraseña.",
            entidadTipo: Usuario::class,
            entidadId: $usuario->id,
        );

        $this->notificaciones->contrasenaModificada($usuario);
    }

    private function registrarFallo(Usuario $usuario, bool $bloqueado): void
    {
        $this->auditoria->registrar(
            modulo: 'Seguridad',
            accion: $bloqueado
                ? 'Bloqueo Temporal de Cambio de Contraseña'
                : 'Intento Fallido de Cambio de Contraseña',
            descripcion: $bloqueado
                ? "Se bloqueó temporalmente el cambio de contraseña de {$usuario->nombre} {$usuario->apellido} por intentos fallidos."
                : "Contraseña actual incorrecta al intentar cambiar la contraseña de {$usuario->nombre} {$usuario->apellido}.",
            entidadTipo: Usuario::class,
            entidadId: $usuario->id,
            detalles: ['bloqueado' => $bloqueado],
            resultado: 'fallido',
        );
    }

    /** Con SESSION_DRIVER=database borra las demás sesiones del usuario. */
    private function cerrarOtrasSesiones(Usuario $usuario, string $sesionActual): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $usuario->getAuthIdentifier())
            ->where('id', '!=', $sesionActual)
            ->delete();
    }
}