<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ExpedienteController;
use App\Models\Usuario;
use App\Models\Expediente;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/login', [LoginController::class, 'procesar'])->name('login.procesar');
});

Route::post('/logout', [LoginController::class, 'salir'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->rol === 'administrador') {
            $usuarios = Usuario::all();

            $expedientesActivos = Expediente::whereNotIn('estado', [
                'Cerrado',
                'Archivado'
            ])->count();

            $usuariosPorRol = [
                [
                    'nombre' => 'Administrador',
                    'total' => $usuarios->where('rol', 'administrador')->count()
                ],
                [
                    'nombre' => 'Secretario',
                    'total' => $usuarios->where('rol', 'secretario')->count()
                ],
                [
                    'nombre' => 'Asesor',
                    'total' => $usuarios->where('rol', 'asesor')->count()
                ],
                [
                    'nombre' => 'Practicante',
                    'total' => $usuarios->where('rol', 'practicante')->count()
                ],
            ];

            $totalUsuarios = $usuarios->count();

            if ($totalUsuarios > 0) {
                foreach ($usuariosPorRol as &$r) {
                    $r['porcentaje'] = round(
                        ($r['total'] / $totalUsuarios) * 100
                    );
                }
                unset($r);
            }

            return Inertia::render('AdminDashboard', [
                'usuarios' => $usuarios,
                'rolesDisponibles' => [
                    'administrador',
                    'secretario',
                    'asesor',
                    'practicante'
                ],
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
            $ultimosExpedientes = Expediente::orderBy(
                'updated_at',
                'desc'
            )
                ->take(5)
                ->get()
                ->map(function ($exp) {
                    return [
                        'id' => $exp->id,
                        'numero' => $exp->numero_expediente,
                        'nombre' => $exp->cliente,
                        'tipo' => $exp->tipo_proceso,
                        'estado' => $exp->estado,
                        'modificado' => $exp->updated_at
                            ? $exp->updated_at->diffForHumans()
                            : 'N/A'
                    ];
                });

            return Inertia::render('SecretarioDashboard', [
                'ultimosExpedientes' => $ultimosExpedientes,
                'estadisticas' => [
                    'audienciasEstaSemana' => 0,
                    'expedientesAbiertos' => Expediente::whereNotIn(
                        'estado',
                        ['Cerrado', 'Archivado']
                    )->count(),
                    'casosSinPracticante' => 0,
                    'notificacionesPendientes' => 0
                ]
            ]);
        }

        if ($user->rol === 'asesor') {
            return Inertia::render('AsesorDashboard');
        }

        if ($user->rol === 'practicante') {
            $casos = Expediente::where('practicante_id', $user->id)
                ->with(['asesor', 'documentos'])
                ->orderBy('updated_at', 'desc')
                ->get();

            $casosAsignados = $casos->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'numero' => $exp->numero_expediente,
                    'nombre' => $exp->cliente,
                    'tipo' => $exp->tipo_proceso,
                    'estado' => strtolower(str_replace(' ', '_', $exp->estado ?? 'pendiente')),
                    'estadoLabel' => $exp->estado ?? 'Abierto',
                    'proximaAudiencia' => 'Sin programar',
                    'totalDocumentos' => $exp->documentos->count(),
                ];
            });

            $primerAsesor = $casos->first()?->asesor;

            return Inertia::render('PracticanteDashboard', [
                'casosAsignados' => $casosAsignados,
                'asesor' => $primerAsesor ? [
                    'nombre' => $primerAsesor->nombre,
                    'apellido' => $primerAsesor->apellido,
                ] : null,
                'estadisticas' => [
                    'casosAsignados' => $casos->count(),
                    'proximasAudiencias' => 0,
                    'casosConActividad' => $casos->where('updated_at', '>=', now()->subDays(7))->count(),
                    'notificacionesNoLeidas' => 0,
                ],
                'notificaciones' => []
            ]);
        }

        abort(403, 'Rol no autorizado.');
    })->name('dashboard');

    Route::resource('usuarios', UsuarioController::class)
        ->middleware('rol:administrador');

    Route::put(
        '/admin/usuarios/{usuario}/rol',
        [UsuarioController::class, 'update']
    )
        ->name('admin.usuarios.updateRol')
        ->middleware('rol:administrador');

    Route::post(
        '/admin/usuarios/{usuario}/reset-password',
        [UsuarioController::class, 'resetPassword']
    )
        ->name('admin.usuarios.resetPassword')
        ->middleware('rol:administrador');

    Route::get(
        '/expedientes',
        [ExpedienteController::class, 'index']
    )
        ->name('expedientes.index')
        ->middleware('rol:administrador,secretario,asesor,practicante');

    Route::get(
        '/expedientes/create',
        [ExpedienteController::class, 'create']
    )
        ->name('expedientes.create')
        ->middleware('rol:secretario');

    Route::post(
        '/expedientes',
        [ExpedienteController::class, 'store']
    )
        ->name('expedientes.store')
        ->middleware('rol:secretario');

    Route::get(
        '/expedientes/exportar/excel',
        [ExpedienteController::class, 'exportarExcel']
    )
        ->name('expedientes.exportar.excel')
        ->middleware('rol:administrador,secretario');

    Route::get(
        '/expedientes/{expediente}/edit',
        [ExpedienteController::class, 'edit']
    )
        ->name('expedientes.edit')
        ->middleware('rol:secretario');

    Route::put(
        '/expedientes/{expediente}',
        [ExpedienteController::class, 'update']
    )
        ->name('expedientes.update')
        ->middleware('rol:secretario');

    Route::put(
        '/expedientes/{expediente}/estado',
        [ExpedienteController::class, 'cambiarEstado']
    )
        ->name('expedientes.estado')
        ->middleware('rol:secretario');

    Route::put(
        '/expedientes/{expediente}/archivar',
        [ExpedienteController::class, 'archivar']
    )
        ->name('expedientes.archivar')
        ->middleware('rol:secretario');

    Route::get(
        '/expedientes/{expediente}/documentos',
        [ExpedienteController::class, 'listarDocumentos']
    )
        ->name('expedientes.documentos.index')
        ->middleware('rol:secretario,asesor,practicante');

    Route::post(
        '/expedientes/{expediente}/documentos',
        [ExpedienteController::class, 'subirDocumento']
    )
        ->name('expedientes.documentos.store')
        ->middleware('rol:secretario,practicante');

    Route::get(
        '/expedientes/{expediente}/historial',
        [ExpedienteController::class, 'historial']
    )
        ->name('expedientes.historial')
        ->middleware('rol:administrador,secretario,asesor');

    Route::get(
        '/documentos/{documento}/ver',
        [ExpedienteController::class, 'verDocumento']
    )
        ->name('documentos.ver')
        ->middleware('rol:secretario,asesor,practicante');

    Route::get(
        '/documentos/{documento}/descargar',
        [ExpedienteController::class, 'descargarDocumento']
    )
        ->name('documentos.descargar')
        ->middleware('rol:secretario,asesor,practicante');

    Route::delete(
        '/documentos/{documento}',
        [ExpedienteController::class, 'eliminarDocumento']
    )
        ->name('documentos.eliminar')
        ->middleware('rol:secretario');

    Route::get(
        '/expedientes/{expediente}',
        [ExpedienteController::class, 'show']
    )
        ->name('expedientes.show')
        ->middleware('rol:secretario,asesor,practicante');
});