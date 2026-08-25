<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expediente;
use App\Models\Usuario;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ExpedienteController extends Controller
{
    public function index()
    {
        // Administradores, Secretarios, Asesores y Practicantes pueden ver.
        $expedientes = Expediente::with(['asesor', 'creador'])->orderBy('created_at', 'desc')->get();
        return Inertia::render('Expedientes/Index', compact('expedientes'));
    }

    public function create()
    {
        // Solo secretario entra aquí (garantizado por middleware web.php)
        $asesores = Usuario::where('rol', 'asesor')->where('activo', true)->get();
        return Inertia::render('Expedientes/Create', compact('asesores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_expediente' => 'required|max:50|unique:expedientes,numero_expediente',
            'cliente'           => 'required|max:150',
            'tipo_proceso'      => 'required|in:Civil,Penal,Laboral,Familia,Administrativo',
            'asesor_id'         => 'required|exists:usuarios,id',
            'fecha_ingreso'     => 'required|date',
        ], [
            'numero_expediente.required' => 'El número de expediente es obligatorio.',
            'numero_expediente.unique'   => 'Este número de expediente ya existe en el sistema.',
            'cliente.required'           => 'La información del cliente es obligatoria.',
            'tipo_proceso.required'      => 'El tipo de proceso es obligatorio.',
            'asesor_id.required'         => 'Debe asignar un asesor responsable.',
            'asesor_id.exists'           => 'El asesor seleccionado no es válido.',
            'fecha_ingreso.required'     => 'La fecha de ingreso es obligatoria.',
        ]);

        $data = $request->all();
        $data['creado_por'] = Auth::id();

        Expediente::create($data);

        return redirect()->route('expedientes.index')->with('exito', 'Expediente registrado exitosamente.');
    }

    public function edit(Expediente $expediente)
    {
        // Solo secretario entra aquí (garantizado por middleware web.php)
        $expediente->load(['asesor', 'creador', 'modificador']);
        $asesores = Usuario::where('rol', 'asesor')->where('activo', true)->get();
        return Inertia::render('Expedientes/Edit', compact('expediente', 'asesores'));
    }

    public function update(Request $request, Expediente $expediente)
    {
        $request->validate([
            'numero_expediente' => 'required|max:50|unique:expedientes,numero_expediente,' . $expediente->id,
            'cliente'           => 'required|max:150',
            'tipo_proceso'      => 'required|in:Civil,Penal,Laboral,Familia,Administrativo',
            'asesor_id'         => 'required|exists:usuarios,id',
            'fecha_ingreso'     => 'required|date',
            'estado'            => 'required|in:Abierto,En Proceso,Resuelto,Cerrado,Archivado',
            'descripcion'       => 'nullable|string'
        ], [
            'numero_expediente.required' => 'El número de expediente es obligatorio.',
            'numero_expediente.unique'   => 'Este número de expediente ya existe en el sistema.',
            'cliente.required'           => 'La información del cliente es obligatoria.',
            'tipo_proceso.required'      => 'El tipo de proceso es obligatorio.',
            'asesor_id.required'         => 'Debe asignar un asesor responsable.',
            'asesor_id.exists'           => 'El asesor seleccionado no es válido.',
            'fecha_ingreso.required'     => 'La fecha de ingreso es obligatoria.',
            'estado.required'            => 'El estado es obligatorio.',
            'estado.in'                  => 'El estado seleccionado no es válido.'
        ]);

        $data = $request->all();
        $data['modificado_por'] = Auth::id();

        $expediente->update($data);

        return redirect()->route('expedientes.index')->with('exito', 'Expediente actualizado exitosamente.');
    }
}
