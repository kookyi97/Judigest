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
            $usuarios = Usuario::all();
            $expedientesActivos = \App\Models\Expediente::whereNotIn('estado', ['Cerrado', 'Archivado'])->count();
            
            $usuariosPorRol = [
                ['nombre' => 'Administrador', 'total' => $usuarios->where('rol', 'administrador')->count()],
                ['nombre' => 'Secretario', 'total' => $usuarios->where('rol', 'secretario')->count()],
                ['nombre' => 'Asesor', 'total' => $usuarios->where('rol', 'asesor')->count()],
                ['nombre' => 'Practicante', 'total' => $usuarios->where('rol', 'practicante')->count()],
            ];
            
            $totalUsuarios = $usuarios->count();
            if ($totalUsuarios > 0) {
                foreach ($usuariosPorRol as &$r) {
                    $r['porcentaje'] = round(($r['total'] / $totalUsuarios) * 100);
                }
            }

            return Inertia::render('AdminDashboard', [
                'usuarios' => $usuarios,
                'rolesDisponibles' => ['administrador', 'secretario', 'asesor', 'practicante'],
                'estadisticas' => [
                    'expedientesActivos' => $expedientesActivos,
                    'usuariosActivos' => $usuarios->where('activo', true)->count(),
                    'audienciasEsteMes' => 0,
                    'accionesHoy' => 0
                ],
                'usuariosPorRol' => $usuariosPorRol
            ]);
        }

        if ($user->rol === 'secretario') {
            $ultimosExpedientes = \App\Models\Expediente::orderBy('updated_at', 'desc')->take(5)->get()->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'numero' => $exp->numero_expediente,
                    'nombre' => $exp->cliente,
                    'tipo' => $exp->tipo_proceso,
                    'estado' => $exp->estado,
                    'modificado' => $exp->updated_at->diffForHumans()
                ];
            });

            return Inertia::render('SecretarioDashboard', [
                'ultimosExpedientes' => $ultimosExpedientes,
                'estadisticas' => [
                    'audienciasEstaSemana' => 0,
                    'expedientesAbiertos' => \App\Models\Expediente::whereNotIn('estado', ['Cerrado', 'Archivado'])->count(),
                    'casosSinPracticante' => 0,
                    'notificacionesPendientes' => 0
                ]
            ]);
        }
        if ($user->rol === 'asesor') { return Inertia::render('AsesorDashboard'); }
        if ($user->rol === 'practicante') { return Inertia::render('PracticanteDashboard'); }

        abort(403, 'Rol no autorizado.');
    })->name('dashboard');

    Route::resource('usuarios', UsuarioController::class)->middleware('rol:administrador');
    
    Route::put('/admin/usuarios/{usuario}/rol', [UsuarioController::class, 'update'])
        ->name('admin.usuarios.updateRol')
        ->middleware('rol:administrador');

    Route::post('/admin/usuarios/{usuario}/reset-password', [UsuarioController::class, 'resetPassword'])
        ->name('admin.usuarios.resetPassword')
        ->middleware('rol:administrador');

    // Rutas para Expedientes
    Route::get('/expedientes', [\App\Http\Controllers\ExpedienteController::class, 'index'])
        ->name('expedientes.index')
        ->middleware('rol:administrador,secretario,asesor,practicante');

    Route::get('/expedientes/create', [\App\Http\Controllers\ExpedienteController::class, 'create'])
        ->name('expedientes.create')
        ->middleware('rol:secretario');

    Route::post('/expedientes', [\App\Http\Controllers\ExpedienteController::class, 'store'])
        ->name('expedientes.store')
        ->middleware('rol:secretario');

    Route::get('/expedientes/{expediente}/edit', [\App\Http\Controllers\ExpedienteController::class, 'edit'])
        ->name('expedientes.edit')
        ->middleware('rol:secretario');

    Route::put('/expedientes/{expediente}', [\App\Http\Controllers\ExpedienteController::class, 'update'])
        ->name('expedientes.update')
        ->middleware('rol:secretario');
});