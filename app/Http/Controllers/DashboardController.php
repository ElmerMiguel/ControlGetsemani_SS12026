<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Muestra la pantalla principal del panel de control.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // En P4 se utilizarán los roles asignados por Spatie.
        $rol = method_exists($user, 'getRoleNames') && $user->getRoleNames()->isNotEmpty()
            ? $user->getRoleNames()->first()
            : 'Usuario del Sistema';

        return view('dashboard', [
            'user' => $user,
            'rol' => $rol,
        ]);
    }
}
