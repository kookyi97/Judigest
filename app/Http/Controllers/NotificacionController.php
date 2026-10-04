<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Services\NotificacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cada usuario solo puede ver y marcar SUS notificaciones.
 * Si intenta tocar la de otro, recibe 404 (no se revela que existe).
 */
class NotificacionController extends Controller
{
    public function __construct(private NotificacionService $servicio)
    {
    }

    public function index(Request $request): Response
    {
        $usuario = $request->user();

        $notificaciones = Notificacion::query()
            ->where('usuario_id', $usuario->id)
            ->with('expediente:id,numero_expediente,asesor_id,practicante_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn (Notificacion $n) => $this->servicio->formatear($n, $usuario));

        return Inertia::render('Notificaciones/Index', [
            'notificaciones' => $notificaciones,
            'noLeidas'       => $this->servicio->noLeidas($usuario),
        ]);
    }

    public function marcarLeida(Request $request, int $notificacion): RedirectResponse
    {
        $registro = Notificacion::where('usuario_id', $request->user()->id)->findOrFail($notificacion);

        if (!$registro->leida) {
            $registro->update(['leida' => true]);
        }

        return back();
    }

    public function marcarTodasLeidas(Request $request): RedirectResponse
    {
        Notificacion::where('usuario_id', $request->user()->id)
            ->where('leida', false)
            ->update(['leida' => true]);

        return back()->with('exito', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}