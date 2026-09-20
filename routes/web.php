<?php

use App\Http\Controllers\Auth\CambiarPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'activo', 'password.cambiado'])->group(function () {
    // Cambio forzado de contraseña temporal
    Route::get('/cambiar-password', [CambiarPasswordController::class, 'show'])->name('password.cambiar');
    Route::post('/cambiar-password', [CambiarPasswordController::class, 'update'])->name('password.cambiar.update');

    // Panel de Control
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
