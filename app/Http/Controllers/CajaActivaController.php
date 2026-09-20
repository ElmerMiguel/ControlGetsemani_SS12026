<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CajaActivaController extends Controller
{
    /**
     * Establece la caja de trabajo activa en la sesión del usuario.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $cajaId = $request->input('caja_id');
        $user = $request->user();

        if ($user->can('cajas.gestionar') && ($cajaId === 'todas' || empty($cajaId))) {
            session()->forget(['caja_activa_id', 'caja_activa_nombre']);

            return back()->with('success', 'Visualizando todas las cajas.');
        }

        $caja = Caja::where('activa', true)->findOrFail($cajaId);

        if (! $user->can('cajas.gestionar') && ! $user->cajas()->where('cajas.id', $caja->id)->exists()) {
            abort(403, 'No tiene acceso a la caja seleccionada.');
        }

        session([
            'caja_activa_id' => $caja->id,
            'caja_activa_nombre' => $caja->nombre,
        ]);

        return back()->with('success', "Caja de trabajo seleccionada: {$caja->nombre}");
    }
}
