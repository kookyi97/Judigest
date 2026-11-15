<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarPasswordRequest;
use App\Services\CambioPasswordService;
use Illuminate\Http\RedirectResponse;

class PasswordPropiaController extends Controller
{
    public function update(CambiarPasswordRequest $request, CambioPasswordService $servicio): RedirectResponse
    {
        $servicio->cambiar(
            $request->user(),
            $request->validated('password_actual'),
            $request->validated('password_nueva'),
            $request,
        );

        return redirect()->route('perfil.show')->with('exito', 'Tu contraseña se cambió correctamente.');
    }
}