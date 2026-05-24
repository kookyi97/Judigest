<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsuarioController;


// Redirigir la raíz al login
Route::get('/', function () {
    return redirect()->route('login');
});
// Rutas públicas (JD001)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/login', [LoginController::class, 'procesar'])->name('login.procesar');
});

// JD002: logout
Route::post('/logout', [LoginController::class, 'salir'])
    ->middleware('auth')->name('logout');

// Rutas protegidas
Route::middleware('auth')->group(function () {

    // Dashboard (JD013) - todos los roles
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gestión de usuarios (JD004) - solo administrador
    Route::resource('usuarios', UsuarioController::class)
        ->middleware('rol:administrador');
});