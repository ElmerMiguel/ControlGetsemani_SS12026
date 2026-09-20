<?php

use App\Http\Controllers\AportanteController;
use App\Http\Controllers\Auth\CambiarPasswordController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CatalogoEgresoController;
use App\Http\Controllers\CatalogoIngresoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsersController;
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

    // Gestión de Usuarios (RN-14)
    Route::patch('/usuarios/{usuario}/estado', [UsersController::class, 'cambiarEstado'])->name('usuarios.estado');
    Route::post('/usuarios/{usuario}/password', [UsersController::class, 'generarPassword'])->name('usuarios.password');
    Route::resource('usuarios', UsersController::class)
        ->parameters(['usuarios' => 'usuario'])
        ->except(['show', 'destroy']);

    // Departamentos (RN-13)
    Route::patch('/departamentos/{departamento}/estado', [DepartamentoController::class, 'cambiarEstado'])->name('departamentos.estado');
    Route::resource('departamentos', DepartamentoController::class)->except(['show', 'destroy']);

    // Cajas (RN-13, RN-15)
    Route::patch('/cajas/{caja}/estado', [CajaController::class, 'cambiarEstado'])->name('cajas.estado');
    Route::resource('cajas', CajaController::class)->except(['destroy']);

    // Catálogos de Cuentas (RN-12)
    Route::patch('/catalogos/ingresos/{catalogo_ingreso}/estado', [CatalogoIngresoController::class, 'cambiarEstado'])->name('catalogos.ingresos.estado');
    Route::resource('catalogos/ingresos', CatalogoIngresoController::class)
        ->names('catalogos.ingresos')
        ->parameters(['ingresos' => 'catalogo_ingreso'])
        ->except(['show', 'destroy']);

    Route::patch('/catalogos/egresos/{catalogo_egreso}/estado', [CatalogoEgresoController::class, 'cambiarEstado'])->name('catalogos.egresos.estado');
    Route::resource('catalogos/egresos', CatalogoEgresoController::class)
        ->names('catalogos.egresos')
        ->parameters(['egresos' => 'catalogo_egreso'])
        ->except(['show', 'destroy']);

    // Padrón de Aportantes
    Route::patch('/aportantes/{aportante}/estado', [AportanteController::class, 'cambiarEstado'])->name('aportantes.estado');
    Route::resource('aportantes', AportanteController::class)->except(['show', 'destroy']);

    // Bitácora de Auditoría (RN-16) - Solo Lectura
    Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    Route::get('/bitacora/{bitacora}', [BitacoraController::class, 'show'])->name('bitacora.show');
});

require __DIR__.'/auth.php';
