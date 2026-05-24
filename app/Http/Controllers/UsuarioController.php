<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
    $usuarios = Usuario::withTrashed()->orderBy('created_at','desc')->get();
    return Inertia::render('Usuarios/Index', compact('usuarios'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
    $request->validate([
        'nombre'     => 'required|max:100',
        'apellido'   => 'required|max:100',
        'correo'     => 'required|email|unique:usuarios,correo',
        'contrasena' => 'required|min:8|confirmed',
        'rol'        => 'required|in:administrador,secretario,asesor,practicante',
    ]);
    // JD044: hash automático via cast 'hashed'
    Usuario::create($request->all());
    return redirect()->back()->with('exito', 'Usuario creado correctamente.');
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Usuario $usuario) {
    $request->validate([
        'nombre'  => 'required|max:100',
        'apellido'=> 'required|max:100',
        'correo'  => 'required|email|unique:usuarios,correo,'.$usuario->id,
        'rol'     => 'required|in:administrador,secretario,asesor,practicante',
        'activo'  => 'boolean',
    ]);
    $data = $request->except('contrasena');
    if ($request->filled('contrasena')) {
        $data['contrasena'] = $request->contrasena; // cast lo hashea
    }
    $usuario->update($data);
    return redirect()->back()->with('exito', 'Usuario actualizado.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
