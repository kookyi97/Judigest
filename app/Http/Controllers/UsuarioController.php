<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Inertia\Inertia;

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
        return Inertia::render('Usuarios/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
    $request->validate([
        'nombre_usuario' => 'required|max:50|unique:usuarios,nombre_usuario',
        'nombre'     => 'required|max:100',
        'apellido'   => 'required|max:100',
        'correo'     => 'required|email|unique:usuarios,correo',
        'contrasena' => 'required|min:8|confirmed',
        'rol'        => 'required|in:administrador,secretario,asesor,practicante',
        'activo'     => 'boolean',
    ], [
        'nombre_usuario.unique' => 'Ya existe un usuario con este nombre de usuario.',
        'correo.unique' => 'Ya existe un usuario registrado con este correo.',
        'contrasena.confirmed' => 'Las contraseñas no coinciden.',
    ]);
    
    // JD044: hash automático via cast 'hashed'
    Usuario::create($request->all());
    
    return redirect()->route('usuarios.index')->with('exito', 'Usuario creado correctamente.');
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
    public function edit(Usuario $usuario)
    {
        $usuario->load('modificador');
        return Inertia::render('Usuarios/Edit', [
            'usuario' => $usuario
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Usuario $usuario) {
    $request->validate([
        'nombre_usuario'=> 'required|max:50|unique:usuarios,nombre_usuario,'.$usuario->id,
        'nombre'  => 'required|max:100',
        'apellido'=> 'required|max:100',
        'correo'  => 'required|email|unique:usuarios,correo,'.$usuario->id,
        'rol'     => 'required|in:administrador,secretario,asesor,practicante',
        'activo'  => 'boolean',
    ], [
        'nombre_usuario.unique' => 'Ya existe un usuario con este nombre de usuario.',
        'correo.unique' => 'Ya existe un usuario registrado con este correo.',
    ]);
    
    $data = $request->except('contrasena');
    if ($request->filled('contrasena')) {
        $data['contrasena'] = $request->contrasena; // cast lo hashea
    }
    
    $data['modificado_por'] = \Illuminate\Support\Facades\Auth::id();

    $usuario->update($data);
    return redirect()->route('usuarios.index')->with('exito', 'Usuario actualizado exitosamente.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
