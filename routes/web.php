<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AsignacionPracticanteController;
use App\Http\Controllers\NotificacionController;
use App\Services\NotificacionService;
use App\Services\AuditoriaService;
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
            $usuarios           = Usuario::all();
            $expedientesActivos = Expediente::whereNotIn('estado', ['Cerrado','Archivado'])->count();

            $usuariosPorRol = [
                ['nombre' => 'Administrador', 'total' => $usuarios->where('rol','administrador')->count()],
                ['nombre' => 'Secretario',    'total' => $usuarios->where('rol','secretario')->count()],
                ['nombre' => 'Asesor',        'total' => $usuarios->where('rol','asesor')->count()],
                ['nombre' => 'Practicante',   'total' => $usuarios->where('rol','practicante')->count()],
            ];
            $totalUsuarios = $usuarios->count();
            if ($totalUsuarios > 0) {
                foreach ($usuariosPorRol as &$r) {
                    $r['porcentaje'] = round(($r['total'] / $totalUsuarios) * 100);
                }
                unset($r);
            }

            $auditoriaService = app(\App\Services\AuditoriaService::class);

            // Audiencias este mes para el admin
            $audienciasEsteMes = \App\Models\Audiencia::whereMonth('fecha', now()->month)
                ->whereYear('fecha', now()->year)
                ->count();

            return Inertia::render('AdminDashboard', [
                'usuarios'          => $usuarios,
                'rolesDisponibles'  => ['administrador','secretario','asesor','practicante'],
                'estadisticas'      => [
                    'expedientesActivos' => $expedientesActivos,
                    'usuariosActivos'    => $usuarios->where('activo', true)->count(),
                    'audienciasEsteMes'  => $audienciasEsteMes,
                    'accionesHoy'        => $auditoriaService->totalAccionesHoy(),
                ],
                'usuariosPorRol'     => $usuariosPorRol,
                'ultimasActividades' => $auditoriaService->ultimasActividades(6),
            ]);
        }

        if ($user->rol === 'secretario') {
            $ultimosExpedientes = Expediente::orderBy('updated_at','desc')
                ->take(5)
                ->get()
                ->map(fn($exp) => [
                    'id'         => $exp->id,
                    'numero'     => $exp->numero_expediente,
                    'nombre'     => $exp->cliente,
                    'tipo'       => $exp->tipo_proceso,
                    'estado'     => strtolower(str_replace(' ', '_', $exp->estado ?? 'pendiente')),
                    'modificado' => $exp->updated_at?->diffForHumans() ?? 'N/A',
                ]);

            // Audiencias de esta semana (lunes a domingo)
            $inicioSemana = now()->startOfWeek();
            $finSemana    = now()->endOfWeek();

            $audienciasEstaSemana = \App\Models\Audiencia::whereBetween('fecha', [$inicioSemana, $finSemana])
                ->where('estado', 'programada')
                ->count();

            // Próximas audiencias para mostrar en el panel
            $proximasAudiencias = \App\Models\Audiencia::with(['expediente.practicante'])
                ->where('estado', 'programada')
                ->where('fecha', '>=', now()->toDateString())
                ->orderBy('fecha')
                ->orderBy('hora')
                ->take(5)
                ->get()
                ->map(fn($a) => [
                    'expediente' => $a->expediente->numero_expediente ?? '',
                    'hora'       => substr($a->hora, 0, 5),
                    'sala'       => $a->sala_juzgado,
                    'dia'        => $a->fecha->format('d'),
                    'mes'        => strtoupper(substr(['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][$a->fecha->month - 1], 0, 3)),
                    'practicante'=> $a->expediente->practicante
                        ? $a->expediente->practicante->nombre . ' ' . $a->expediente->practicante->apellido
                        : 'Sin practicante',
                ]);

            return Inertia::render('SecretarioDashboard', [
                'ultimosExpedientes' => $ultimosExpedientes,
                'proximasAudiencias' => $proximasAudiencias,
                'estadisticas'       => [
                    'audienciasEstaSemana'     => $audienciasEstaSemana,
                    'expedientesAbiertos'      => Expediente::whereNotIn('estado', ['Cerrado','Archivado'])->count(),
                    'casosSinPracticante'      => Expediente::whereNull('practicante_id')->whereNotIn('estado', ['Cerrado','Archivado'])->count(),
                    'notificacionesPendientes' => 0,
                ],
            ]);
        }

        if ($user->rol === 'asesor') {
            // Próximas audiencias solo de sus casos
            $proximasAudiencias = \App\Models\Audiencia::with(['expediente.practicante'])
                ->whereHas('expediente', fn($q) => $q->where('asesor_id', $user->id))
                ->where('estado', 'programada')
                ->where('fecha', '>=', now()->toDateString())
                ->orderBy('fecha')
                ->orderBy('hora')
                ->take(5)
                ->get()
                ->map(fn($a) => [
                    'expediente'  => $a->expediente->numero_expediente ?? '',
                    'tipo'        => $a->tipo_audiencia,
                    'hora'        => substr($a->hora, 0, 5),
                    'sala'        => $a->sala_juzgado,
                    'practicante' => $a->expediente->practicante
                        ? $a->expediente->practicante->nombre . ' ' . $a->expediente->practicante->apellido
                        : 'Sin practicante',
                ]);

            $casosActivos = Expediente::where('asesor_id', $user->id)
                ->whereNotIn('estado', ['Cerrado','Archivado'])
                ->count();

            $audienciasProximas = \App\Models\Audiencia::whereHas('expediente', fn($q) => $q->where('asesor_id', $user->id))
                ->where('estado', 'programada')
                ->where('fecha', '>=', now()->toDateString())
                ->count();

            // Practicantes que hoy trabajan casos activos de este asesor, con su carga
            $practicantes = Expediente::where('asesor_id', $user->id)
                ->activos()
                ->whereNotNull('practicante_id')
                ->with('practicante:id,nombre,apellido')
                ->get()
                ->groupBy('practicante_id')
                ->map(fn ($casos) => [
                    'id'          => $casos->first()->practicante_id,
                    'nombre'      => trim($casos->first()->practicante->nombre . ' ' . $casos->first()->practicante->apellido),
                    'iniciales'   => mb_strtoupper(mb_substr($casos->first()->practicante->nombre, 0, 1) . mb_substr($casos->first()->practicante->apellido, 0, 1)),
                    'casosActivos'=> $casos->count(),
                ])
                ->values();

            return Inertia::render('AsesorDashboard', [
                'proximasAudiencias' => $proximasAudiencias,
                'estadisticas'       => [
                    'casosActivos'          => $casosActivos,
                    'practicantesAsignados' => $practicantes->count(),
                    'casosSinActividad'     => 0,
                    'documentosPendientes'  => 0,
                    'proximasAudiencias'    => $audienciasProximas,
                ],
                'practicantes' => $practicantes,
            ]);
        }

        if ($user->rol === 'practicante') {
            $notificaciones = app(NotificacionService::class);

            $casos = Expediente::visiblesPara($user)
                ->with(['asesor:id,nombre,apellido'])
                ->orderBy('updated_at','desc')
                ->get();
                
            $casosAsignados = $casos->map(fn($exp) => [
                'id'             => $exp->id,
                'numero'         => $exp->numero_expediente,
                'nombre'         => $exp->cliente,
                'tipo'           => $exp->tipo_proceso,
                'estado'         => strtolower(str_replace(' ', '_', $exp->estado ?? 'pendiente')),
                'estadoLabel'    => $exp->estado ?? 'Abierto',
                'proximaAudiencia' => 'Sin programar',
            ]);

            // Audiencias próximas del practicante
            $audienciasProximas = \App\Models\Audiencia::whereHas('expediente', fn($q) => $q->where('practicante_id', $user->id))
                ->where('estado', 'programada')
                ->where('fecha', '>=', now()->toDateString())
                ->count();

            $primerAsesor = $casos->first()?->asesor;

            return Inertia::render('PracticanteDashboard', [
                'casosAsignados' => $casosAsignados,
                'asesor'         => $primerAsesor ? [
                    'nombre'   => $primerAsesor->nombre,
                    'apellido' => $primerAsesor->apellido,
                ] : null,
                'estadisticas'   => [
                    'casosAsignados'         => $casos->count(),
                    'proximasAudiencias'     => $audienciasProximas,
                    'casosConActividad'      => $casos->where('updated_at', '>=', now()->subDays(7))->count(),
                    'notificacionesNoLeidas' => $notificaciones->noLeidas($user),
                ],
                'notificaciones' => $notificaciones->recientes($user, 5),
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
        '/configuracion',
        [ConfiguracionController::class, 'index']
    )
        ->name('configuracion.index')
        ->middleware('rol:administrador');

    Route::put(
        '/configuracion',
        [ConfiguracionController::class, 'update']
    )
        ->name('configuracion.update')
        ->middleware('rol:administrador');

    Route::get(
        '/auditoria',
        [AuditoriaController::class, 'index']
    )
        ->name('auditoria.index')
        ->middleware('rol:administrador');

    Route::get(
        '/auditoria/exportar/excel',
        [AuditoriaController::class, 'exportarExcel']
    )
        ->name('auditoria.exportar.excel')
        ->middleware('rol:administrador');

    Route::get(
        '/auditoria/exportar/csv',
        [AuditoriaController::class, 'exportarCsv']
    )
        ->name('auditoria.exportar.csv')
        ->middleware('rol:administrador');

    Route::get(
        '/expedientes',
        [ExpedienteController::class, 'index']
    )
        ->name('expedientes.index')
        ->middleware('rol:administrador,secretario,asesor,practicante');

    // Refresco automático del listado (devuelve solo una huella, no datos)
    Route::get(
        '/expedientes/version',
        [ExpedienteController::class, 'version']
    )
        ->name('expedientes.version')
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

    // JD035 — Asignar practicante (solo el Asesor responsable)
    Route::post(
        '/expedientes/{expediente}/practicante',
        [AsignacionPracticanteController::class, 'store']
    )
        ->name('expedientes.practicante.asignar')
        ->middleware('rol:asesor');

    // JD036 — Reasignar caso a otro practicante (solo el Asesor responsable)
    Route::put(
        '/expedientes/{expediente}/practicante',
        [AsignacionPracticanteController::class, 'update']
    )
        ->name('expedientes.practicante.reasignar')
        ->middleware('rol:asesor');

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
    '/documentos/{documento}/ver/{nombre?}',
    [ExpedienteController::class, 'verDocumento']
    )
    ->whereNumber('documento')
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

    Route::get('/audiencias', [App\Http\Controllers\AudienciaController::class, 'index'])
        ->name('audiencias.index')
        ->middleware('rol:administrador,secretario,asesor,practicante');

    Route::get('/audiencias/create', [App\Http\Controllers\AudienciaController::class, 'create'])
        ->name('audiencias.create')
        ->middleware('rol:secretario');

    Route::post('/audiencias', [App\Http\Controllers\AudienciaController::class, 'store'])
        ->name('audiencias.store')
        ->middleware('rol:secretario');

    Route::get('/audiencias/{audiencia}/edit', [App\Http\Controllers\AudienciaController::class, 'edit'])
        ->name('audiencias.edit')
        ->middleware('rol:secretario');

    Route::put('/audiencias/{audiencia}', [App\Http\Controllers\AudienciaController::class, 'update'])
        ->name('audiencias.update')
        ->middleware('rol:secretario');

    Route::patch('/audiencias/{audiencia}/cancelar', [App\Http\Controllers\AudienciaController::class, 'cancelar'])
        ->name('audiencias.cancelar')
        ->middleware('rol:secretario');

    Route::get('/calendario', [App\Http\Controllers\AudienciaController::class, 'calendario'])
        ->name('calendario.index')
         ->middleware('rol:administrador,secretario,asesor,practicante');

    // JD040 / JD041 — Notificaciones propias del asesor y del practicante
    Route::get('/notificaciones', [NotificacionController::class, 'index'])
        ->name('notificaciones.index')
        ->middleware('rol:asesor,practicante');

    Route::patch('/notificaciones/leidas', [NotificacionController::class, 'marcarTodasLeidas'])
        ->name('notificaciones.leidas')
        ->middleware('rol:asesor,practicante');

    Route::patch('/notificaciones/{notificacion}/leida', [NotificacionController::class, 'marcarLeida'])
        ->whereNumber('notificacion')
        ->name('notificaciones.leida')
        ->middleware('rol:asesor,practicante');

});