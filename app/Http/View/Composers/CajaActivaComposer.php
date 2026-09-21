<?php

namespace App\Http\View\Composers;

use App\Models\Caja;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CajaActivaComposer
{
    /**
     * Inyecta la caja activa y la lista de cajas accesibles en la vista.
     */
    public function compose(View $view): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($user->can('cajas.gestionar')) {
            $cajasDisponibles = Caja::where('activa', true)->orderBy('nombre')->get();
        } else {
            $cajasDisponibles = $user->cajas()->where('activa', true)->orderBy('nombre')->get();
        }

        $cajaActivaId = session('caja_activa_id');
        $cajaActiva = null;

        if ($cajaActivaId && $cajaActivaId !== 'todas') {
            $cajaActiva = $cajasDisponibles->firstWhere('id', $cajaActivaId);
        }

        // Si el tesorero solo tiene una caja asignada, seleccionarla por defecto
        if (! $cajaActiva && ! $user->can('cajas.gestionar') && $cajasDisponibles->count() === 1) {
            $cajaActiva = $cajasDisponibles->first();
            $cajaActivaId = $cajaActiva->id;
            session([
                'caja_activa_id' => $cajaActiva->id,
                'caja_activa_nombre' => $cajaActiva->nombre,
            ]);
        }

        $view->with('cajasDisponibles', $cajasDisponibles);
        $view->with('cajaActiva', $cajaActiva);
        $view->with('cajaActivaId', $cajaActiva?->id);
    }
}
