<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Usuario;

class LoginController extends Controller
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 15;

    // JD001: mostrar formulario
    public function mostrar()
    {
        return Inertia::render('Auth/Login');
    }

    // JD001 + JD044 + JD045: procesar login
    public function procesar(Request $request)
    {
        $request->validate([
            'correo'     => 'required|email',
            'contrasena' => 'required|string',
        ]);

        $usuario = Usuario::where('correo', $request->correo)->first();

        // JD045: verificar bloqueo
        if ($usuario && $usuario->estaBloqueado()) {
            $minutos = now()->diffInMinutes($usuario->bloqueado_hasta);
            return back()->withErrors([
                'correo' => "Cuenta bloqueada. Intenta en {$minutos} minuto(s)."
            ]);
        }

        // CORRECCIÓN: Auth::attempt siempre usa 'password' como clave
        // getAuthPassword() en el modelo lo mapea a 'contrasena'
        if (Auth::attempt([
            'correo'   => $request->correo,
            'password' => $request->contrasena,
        ], $request->boolean('recordar'))) {

            $usuario = Auth::user();

            if (!$usuario->activo) {
                Auth::logout();
                return back()->withErrors(['correo' => 'Tu cuenta está desactivada.']);
            }

            // Resetear intentos fallidos
            $usuario->update([
                'intentos_fallidos' => 0,
                'bloqueado_hasta'   => null,
            ]);

            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        // JD045: incrementar intentos fallidos
        if ($usuario) {
            $intentos = $usuario->intentos_fallidos + 1;
            $bloqueo  = null;
            $accion   = 'intento_fallido';

            if ($intentos >= self::MAX_INTENTOS) {
                $bloqueo = now()->addMinutes(self::MINUTOS_BLOQUEO);
                $accion  = 'bloqueado';
            }

            $usuario->update([
                'intentos_fallidos' => $intentos,
                'bloqueado_hasta'   => $bloqueo,
            ]);

            if ($bloqueo) {
                return back()->withErrors([
                    'correo' => 'Demasiados intentos. Cuenta bloqueada por 15 minutos.'
                ]);
            }

            $restantes = self::MAX_INTENTOS - $intentos;
            return back()->withErrors([
                'correo' => "Credenciales incorrectas. Te quedan {$restantes} intento(s)."
            ]);
        }

        return back()->withErrors(['correo' => 'Correo o contraseña incorrectos.']);
    }

    // JD002: logout
    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}