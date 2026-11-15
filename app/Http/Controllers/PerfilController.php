<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarPerfilRequest;
use App\Services\ConfiguracionService;
use App\Services\PerfilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function __construct(private PerfilService $perfil)
    {
    }

    public function show(Request $request): Response
    {
        $usuario = $request->user();

        foreach (['id', 'usuario_id', 'usuario'] as $clave) {
            if ($request->query($clave) !== null && (string) $request->query($clave) !== (string) $usuario->id) {
                $this->perfil->denegarAccesoAjeno($usuario, "{$clave}=" . (string) $request->query($clave));
            }
        }

        return Inertia::render('Perfil/Index', [
            'perfil'          => $this->perfil->datosPara($usuario),
            'longitudMinPass' => max(8, (int) app(ConfiguracionService::class)->get('seguridad_longitud_min_password', 8)),
        ]);
    }

    public function update(ActualizarPerfilRequest $request): RedirectResponse
    {
        $cambiados = $this->perfil->actualizar($request->user(), $request->validated());

        return redirect()->route('perfil.show')->with(
            'exito',
            $cambiados === [] ? 'No hay cambios que guardar.' : 'Tu perfil se actualizó correctamente.'
        );
    }

    /** /perfil/{id}: nunca se sirve; se audita y se deniega. */
    public function otroPerfil(Request $request, string $identificador): never
    {
        $this->perfil->denegarAccesoAjeno($request->user(), $identificador);
    }
}