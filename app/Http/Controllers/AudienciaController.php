<?php

namespace App\Http\Controllers;

use App\Models\Audiencia;
use App\Models\Expediente;
use App\Models\Notificacion;
use App\Services\AuditoriaService;
use App\Services\HistorialExpedienteService;
use App\Services\NotificacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AudienciaController extends Controller
{
    protected AuditoriaService $auditoriaService;
    protected HistorialExpedienteService $historialService;

    protected NotificacionService $notificaciones;

    public function __construct(
        AuditoriaService $auditoriaService,
        HistorialExpedienteService $historialService,
        NotificacionService $notificaciones
    ) {
        $this->auditoriaService = $auditoriaService;
        $this->historialService = $historialService;
        $this->notificaciones = $notificaciones;
    }

    // ─────────────────────────────────────────────────────────────────────
    // JD023 — Listado de audiencias
    // FIX: filtra por rol para que el asesor solo vea las suyas
    // ─────────────────────────────────────────────────────────────────────
    public function index()
    {
        $usuario = Auth::user();

        $query = Audiencia::with(['expediente.asesor', 'expediente.practicante', 'registrador'])
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc');

        // Asesor: solo ve audiencias de expedientes donde es responsable
        if ($usuario->rol === 'asesor') {
            $query->whereHas('expediente', fn($q) => $q->where('asesor_id', $usuario->id));
        }

        // Practicante: solo ve audiencias de sus expedientes asignados
        if ($usuario->rol === 'practicante') {
            $query->whereHas('expediente', fn($q) => $q->where('practicante_id', $usuario->id));
        }

        // Secretario y Administrador ven todas

        $audiencias = $query->get()->map(fn($a) => $this->formatearAudiencia($a));

        // Solo secretario/administrador reciben el catálogo completo de expedientes.
        // Asesor y practicante no deben recibir expedientes ajenos.
        $expedientes = in_array($usuario->rol, ['administrador', 'secretario'], true)
            ? Expediente::activos()
                ->with('practicante:id,nombre,apellido')
                ->orderBy('numero_expediente')
                ->get(['id', 'numero_expediente', 'cliente', 'practicante_id'])
            : collect();

        return Inertia::render('Audiencias/Index', [
            'audiencias'  => $audiencias,
            'expedientes' => $expedientes,
            'rolUsuario'  => $usuario->rol,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // JD023 — Formulario de registro
    // ─────────────────────────────────────────────────────────────────────
    public function create()
    {
        $expedientes = Expediente::whereNotIn('estado', ['Archivado', 'Cerrado'])
            ->with('practicante')
            ->orderBy('numero_expediente')
            ->get(['id', 'numero_expediente', 'cliente', 'practicante_id']);

        return Inertia::render('Audiencias/Create', [
            'expedientes' => $expedientes,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // JD023 — Guardar nueva audiencia
    // ─────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'expediente_id' => 'required|exists:expedientes,id',
            'fecha'         => 'required|date|after_or_equal:today',
            'hora'          => 'required|date_format:H:i',
            'tipo_audiencia'=> 'required|string|max:100',
            'sala_juzgado'  => 'required|string|max:150',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'expediente_id.required' => 'Debe seleccionar un expediente.',
            'expediente_id.exists'   => 'El expediente seleccionado no existe.',
            'fecha.required'         => 'La fecha de la audiencia es obligatoria.',
            'fecha.after_or_equal'   => 'La fecha no puede ser anterior a hoy.',
            'hora.required'          => 'La hora de la audiencia es obligatoria.',
            'hora.date_format'       => 'El formato de hora debe ser HH:MM.',
            'tipo_audiencia.required'=> 'El tipo de audiencia es obligatorio.',
            'sala_juzgado.required'  => 'La sala o juzgado es obligatorio.',
        ]);

        $expediente = Expediente::with('practicante')->findOrFail($data['expediente_id']);

        $audiencia = Audiencia::create([
            ...$data,
            'registrado_por' => Auth::id(),
            'estado'         => 'programada',
        ]);

        // JD025 — Notificación automática al practicante
        if ($expediente->practicante_id) {
            Notificacion::notificarAudiencia(
                usuarioId:    $expediente->practicante_id,
                expedienteId: $expediente->id,
                audienciaId:  $audiencia->id,
                tipo:         'audiencia_registrada',
                titulo:       'Nueva audiencia programada',
                mensaje:      "Se ha registrado una audiencia para el expediente {$expediente->numero_expediente}. "
                            . "Fecha: {$audiencia->fecha->format('d/m/Y')} a las " . substr($audiencia->hora, 0, 5)
                            . " en {$audiencia->sala_juzgado}.",
            );
        }

        $this->historialService->registrar(
            expedienteId: $expediente->id,
            accion:       'Audiencia Registrada',
            descripcion:  "Se registró audiencia tipo '{$audiencia->tipo_audiencia}' para el "
                        . $audiencia->fecha->format('d/m/Y') . ' a las ' . substr($audiencia->hora, 0, 5),
            detalles: [
                'audiencia_id' => $audiencia->id,
                'tipo'         => $audiencia->tipo_audiencia,
                'sala'         => $audiencia->sala_juzgado,
                'fecha'        => $audiencia->fecha->format('Y-m-d'),
                'hora'         => $audiencia->hora,
            ]
        );

        $this->auditoriaService->registrar(
            modulo:      'Audiencias',
            accion:      'Registrar Audiencia',
            descripcion: "Se registró audiencia para expediente {$expediente->numero_expediente}",
            entidadTipo: Audiencia::class,
            entidadId:   $audiencia->id,
            detalles: [
                'expediente_id' => $expediente->id,
                'fecha'         => $audiencia->fecha->format('Y-m-d'),
                'hora'          => $audiencia->hora,
            ]
        );

        return redirect()
            ->route('audiencias.index')
            ->with('exito', "Audiencia registrada correctamente para el expediente {$expediente->numero_expediente}.");
    }

    // ─────────────────────────────────────────────────────────────────────
    // JD024 — Formulario de edición
    // ─────────────────────────────────────────────────────────────────────
    public function edit(Audiencia $audiencia)
    {
        $audiencia->load(['expediente.practicante', 'registrador']);

        $expedientes = Expediente::whereNotIn('estado', ['Archivado', 'Cerrado'])
            ->with('practicante')
            ->orderBy('numero_expediente')
            ->get(['id', 'numero_expediente', 'cliente', 'practicante_id']);

        return Inertia::render('Audiencias/Edit', [
            'audiencia'   => $this->formatearAudiencia($audiencia),
            'expedientes' => $expedientes,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // JD024 — Guardar edición
    // ─────────────────────────────────────────────────────────────────────
    public function update(Request $request, Audiencia $audiencia)
    {
        $data = $request->validate([
            'expediente_id' => 'required|exists:expedientes,id',
            'fecha'         => 'required|date',
            'hora'          => 'required|date_format:H:i',
            'tipo_audiencia'=> 'required|string|max:100',
            'sala_juzgado'  => 'required|string|max:150',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'expediente_id.required' => 'Debe seleccionar un expediente.',
            'fecha.required'         => 'La fecha es obligatoria.',
            'hora.required'          => 'La hora es obligatoria.',
            'hora.date_format'       => 'Formato de hora debe ser HH:MM.',
            'tipo_audiencia.required'=> 'El tipo de audiencia es obligatorio.',
            'sala_juzgado.required'  => 'La sala o juzgado es obligatorio.',
        ]);

        $expediente = Expediente::with('practicante')->findOrFail($data['expediente_id']);

        $audiencia->update($data);

        $this->notificaciones->audiencia(
            expediente: $expediente,
            audiencia:  $audiencia,
            tipo:       'audiencia_modificada',
            titulo:     'Audiencia actualizada',
            mensaje:    "La audiencia del expediente {$expediente->numero_expediente} fue modificada. "
                      . "Nueva fecha: {$audiencia->fecha->format('d/m/Y')} a las " . substr($audiencia->hora, 0, 5)
                      . " en {$audiencia->sala_juzgado}.",
            actor:      Auth::user(),
        );

        $this->historialService->registrar(
            expedienteId: $expediente->id,
            accion:       'Audiencia Modificada',
            descripcion:  "Se modificó la audiencia del {$audiencia->fecha->format('d/m/Y')} a las " . substr($audiencia->hora, 0, 5),
            detalles: [
                'audiencia_id' => $audiencia->id,
                'tipo'         => $audiencia->tipo_audiencia,
                'sala'         => $audiencia->sala_juzgado,
            ]
        );

        $this->auditoriaService->registrar(
            modulo:      'Audiencias',
            accion:      'Editar Audiencia',
            descripcion: "Se editó audiencia #{$audiencia->id} del expediente {$expediente->numero_expediente}",
            entidadTipo: Audiencia::class,
            entidadId:   $audiencia->id,
        );

        return redirect()
            ->route('audiencias.index')
            ->with('exito', 'Audiencia actualizada correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // JD024 — Cancelar audiencia
    // ─────────────────────────────────────────────────────────────────────
    public function cancelar(Audiencia $audiencia)
    {
        $audiencia->load('expediente.practicante');

        $audiencia->update(['estado' => 'cancelada']);

        $this->notificaciones->audiencia(
            expediente: $audiencia->expediente,
            audiencia:  $audiencia,
            tipo:       'audiencia_modificada',
            titulo:     'Audiencia cancelada',
            mensaje:    "La audiencia del {$audiencia->fecha->format('d/m/Y')} a las "
                      . substr($audiencia->hora, 0, 5)
                      . " del expediente {$audiencia->expediente->numero_expediente} fue cancelada.",
            actor:      Auth::user(),
        );

        $this->historialService->registrar(
            expedienteId: $audiencia->expediente_id,
            accion:       'Audiencia Cancelada',
            descripcion:  "Se canceló la audiencia programada para el {$audiencia->fecha->format('d/m/Y')}",
            detalles: ['audiencia_id' => $audiencia->id]
        );

        $this->auditoriaService->registrar(
            modulo:      'Audiencias',
            accion:      'Cancelar Audiencia',
            descripcion: "Se canceló audiencia #{$audiencia->id}",
            entidadTipo: Audiencia::class,
            entidadId:   $audiencia->id,
        );

        return back()->with('exito', 'Audiencia cancelada correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // JD026/JD027/JD028 — Calendario filtrado por rol
    // ─────────────────────────────────────────────────────────────────────
    public function calendario()
    {
        $usuario = Auth::user();

        $query = Audiencia::with(['expediente.asesor', 'expediente.practicante'])
            ->where('estado', 'programada');

        if ($usuario->rol === 'asesor') {
            $query->whereHas('expediente', fn($q) => $q->where('asesor_id', $usuario->id));
        }

        if ($usuario->rol === 'practicante') {
            $query->whereHas('expediente', fn($q) => $q->where('practicante_id', $usuario->id));
        }

        $audiencias = $query->orderBy('fecha')->orderBy('hora')->get()
            ->map(fn($a) => [
                'id'            => $a->id,
                'expediente_id' => $a->expediente_id,
                'numero_exp'    => $a->expediente->numero_expediente ?? '',
                'cliente'       => $a->expediente->cliente ?? '',
                'tipo_audiencia'=> $a->tipo_audiencia,
                'sala_juzgado'  => $a->sala_juzgado,
                'fecha'         => $a->fecha->format('Y-m-d'),
                'hora'          => substr($a->hora, 0, 5),
                'observaciones' => $a->observaciones,
                'practicante'   => $a->expediente->practicante
                    ? $a->expediente->practicante->nombre . ' ' . $a->expediente->practicante->apellido
                    : 'Sin asignar',
                'asesor'        => $a->expediente->asesor
                    ? $a->expediente->asesor->nombre . ' ' . $a->expediente->asesor->apellido
                    : 'Sin asignar',
            ]);

        return Inertia::render('Calendario/Index', [
            'audiencias' => $audiencias,
            'rolUsuario' => $usuario->rol,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helper privado
    // ─────────────────────────────────────────────────────────────────────
    private function formatearAudiencia(Audiencia $a): array
    {
        return [
            'id'             => $a->id,
            'expediente_id'  => $a->expediente_id,
            'numero_exp'     => $a->expediente->numero_expediente ?? '',
            'cliente'        => $a->expediente->cliente ?? '',
            'tipo_audiencia' => $a->tipo_audiencia,
            'sala_juzgado'   => $a->sala_juzgado,
            'fecha'          => $a->fecha->format('Y-m-d'),
            'fecha_display'  => $a->fecha->format('d/m/Y'),
            'hora'           => substr($a->hora, 0, 5),
            'observaciones'  => $a->observaciones,
            'estado'         => $a->estado,
            'registrado_por' => $a->registrador
                ? $a->registrador->nombre . ' ' . $a->registrador->apellido
                : 'Sistema',
            'practicante'    => $a->expediente->practicante
                ? $a->expediente->practicante->nombre . ' ' . $a->expediente->practicante->apellido
                : 'Sin asignar',
            'practicante_id' => $a->expediente->practicante_id ?? null,
            'asesor'         => $a->expediente->asesor
                ? $a->expediente->asesor->nombre . ' ' . $a->expediente->asesor->apellido
                : 'Sin asignar',
            'created_at'     => $a->created_at?->format('d/m/Y H:i'),
            'conflicto'      => $a->tieneConflicto(),
        ];
    }
}
