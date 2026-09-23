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
        $usuarios = Usuario::with('modificador')->orderBy('created_at','desc')->get();
        $minPassword = (int) app(\App\Services\ConfiguracionService::class)->get('seguridad_longitud_min_password', 8);
        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
            'longitudMinimaPassword' => $minPassword,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $minPassword = (int) app(\App\Services\ConfiguracionService::class)->get('seguridad_longitud_min_password', 8);
        return Inertia::render('Usuarios/Create', [
            'longitudMinimaPassword' => $minPassword,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $minPassword = (int) app(\App\Services\ConfiguracionService::class)->get('seguridad_longitud_min_password', 8);
        $request->validate([
            'nombre_usuario' => 'required|max:50|unique:usuarios,nombre_usuario',
            'nombre'     => 'required|max:100',
            'apellido'   => 'required|max:100',
            'correo'     => 'required|email|unique:usuarios,correo',
            'contrasena' => "required|min:{$minPassword}|confirmed",
            'rol'        => 'required|in:administrador,secretario,asesor,practicante',
            'activo'     => 'boolean',
        ], [
            'nombre_usuario.unique' => 'Ya existe un usuario con este nombre de usuario.',
            'correo.unique' => 'Ya existe un usuario registrado con este correo.',
            'contrasena.min' => "La contraseña debe tener al menos {$minPassword} caracteres.",
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
    public function destroy(Usuario $usuario)
    {
        abort_if(
            \Illuminate\Support\Facades\Auth::id() === $usuario->id,
            403,
            'No puedes eliminar tu propia cuenta de usuario.'
        );

        $usuarioNombre = "{$usuario->nombre} {$usuario->apellido} (@{$usuario->nombre_usuario})";
        $rol = $usuario->rol;
        $id = $usuario->id;
        $admin = \Illuminate\Support\Facades\Auth::user();
        $adminNombre = $admin ? "{$admin->nombre} {$admin->apellido}" : 'El administrador';

        $usuario->delete();

        // Registrar en Auditoría con todos los detalles
        app(\App\Services\AuditoriaService::class)->registrar(
            modulo: 'Usuarios',
            accion: 'Eliminar Usuario',
            descripcion: "El usuario {$adminNombre} eliminó la cuenta del usuario {$usuarioNombre} con rol {$rol}.",
            entidadTipo: Usuario::class,
            entidadId: $id,
            detalles: [
                'metodo' => 'DELETE',
                'usuario_eliminado_id' => $id,
                'nombre_completo' => "{$usuario->nombre} {$usuario->apellido}",
                'nombre_usuario' => $usuario->nombre_usuario,
                'correo' => $usuario->correo,
                'rol' => $rol,
            ],
            resultado: 'exitoso',
            request: request(),
            usuario: $admin
        );

        request()->attributes->set('auditoria_registrada', true);

        return redirect()->route('usuarios.index')->with('exito', 'Usuario eliminado correctamente.');
    }

    /**
     * Restablecer la contraseña de un usuario por una ingresada manualmente por el admin.
     */
    public function resetPassword(Request $request, Usuario $usuario)
    {
        $minPassword = (int) app(\App\Services\ConfiguracionService::class)->get('seguridad_longitud_min_password', 8);
        $request->validate([
            'contrasena' => "required|min:{$minPassword}"
        ], [
            'contrasena.required' => 'La nueva contraseña es obligatoria.',
            'contrasena.min' => "La nueva contraseña debe tener al menos {$minPassword} caracteres."
        ]);

        // Actualizar el usuario (el mutador/cast aplicará el hash)
        $usuario->update([
            'contrasena' => $request->contrasena,
            'modificado_por' => \Illuminate\Support\Facades\Auth::id()
        ]);
        
        // Retornar mensaje de éxito
        return back()->with('exito', 'Contraseña actualizada exitosamente.');
    }
}
