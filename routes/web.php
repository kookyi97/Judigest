<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Models\Usuario;
use Inertia\Inertia; // <--- Asegúrate de tener esta línea para que funcione Render

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/login', [LoginController::class, 'procesar'])->name('login.procesar');
});

Route::post('/logout', [LoginController::class, 'salir'])
    ->middleware('auth')->name('logout');

// Rutas protegidas
Route::middleware('auth')->group(function () {

    // Aquí ya NO se menciona a DashboardController por ningún lado
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->rol === 'administrador') {
            return Inertia::render('AdminDashboard', [
                'usuarios' => Usuario::all(),
                'rolesDisponibles' => ['administrador', 'secretario', 'asesor', 'practicante']
            ]);
        }

        if ($user->rol === 'secretario') { return Inertia::render('SecretarioDashboard'); }
        if ($user->rol === 'asesor') { return Inertia::render('AsesorDashboard'); }
        if ($user->rol === 'practicante') { return Inertia::render('PracticanteDashboard'); }

        abort(403, 'Rol no autorizado.');
    })->name('dashboard');

    Route::resource('usuarios', UsuarioController::class)->middleware('rol:administrador');
    
    Route::put('/admin/usuarios/{usuario}/rol', [UsuarioController::class, 'update'])
        ->name('admin.usuarios.updateRol')
        ->middleware('rol:administrador');
});