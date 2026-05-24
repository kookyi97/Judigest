<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Usuario;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsuarios  = Usuario::count();
        $usuariosPorRol = Usuario::groupBy('rol')
            ->selectRaw('rol, count(*) as total')
            ->pluck('total', 'rol');

        return Inertia::render('Dashboard', [
            'totalUsuarios'  => $totalUsuarios,
            'usuariosPorRol' => $usuariosPorRol,
            'ultimosAccesos' => [],
        ]);
    }
}