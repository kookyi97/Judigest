<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsignarPracticanteRequest;
use App\Models\Expediente;
use App\Services\AsignacionPracticanteService;
use Closure;
use DomainException;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador delgado: la autorización vive en el FormRequest
 * y las reglas de negocio en AsignacionPracticanteService.
 */
class AsignacionPracticanteController extends Controller
{
    public function __construct(private AsignacionPracticanteService $servicio)
    {
    }

    // JD035
    public function store(AsignarPracticanteRequest $request, Expediente $expediente): RedirectResponse
    {
        return $this->ejecutar(
            fn () => $this->servicio->asignar($expediente, $request->integer('practicante_id'), $request->user()),
            'Practicante asignado correctamente. Se le envió una notificación.'
        );
    }

    // JD036
    public function update(AsignarPracticanteRequest $request, Expediente $expediente): RedirectResponse
    {
        return $this->ejecutar(
            fn () => $this->servicio->reasignar($expediente, $request->integer('practicante_id'), $request->user()),
            'Caso reasignado correctamente. Se notificó al nuevo practicante.'
        );
    }

    private function ejecutar(Closure $accion, string $mensajeExito): RedirectResponse
    {
        try {
            $accion();
        } catch (DomainException $e) {
            return back()->withErrors(['practicante_id' => $e->getMessage()]);
        }

        return back()->with('exito', $mensajeExito);
    }
}