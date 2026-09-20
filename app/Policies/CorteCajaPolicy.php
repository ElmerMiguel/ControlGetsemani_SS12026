<?php

namespace App\Policies;

use App\Enums\EstadoCorte;
use App\Models\Caja;
use App\Models\CorteCaja;
use App\Models\User;

class CorteCajaPolicy
{
    /**
     * Determina si el usuario puede listar los cortes de caja.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('cortes.solicitar') || $user->can('cortes.aprobar');
    }

    /**
     * RN-15: Determina si el usuario puede ver el detalle del corte (aislamiento por caja).
     */
    public function view(User $user, CorteCaja $corte): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Tesorero solo puede ver cortes de sus cajas asignadas
        return $user->can('cortes.solicitar')
            && $user->cajas()->where('cajas.id', $corte->caja_id)->exists();
    }

    /**
     * RN-08: Determina si el usuario puede solicitar un corte para una caja determinada.
     */
    public function create(User $user, ?Caja $caja = null): bool
    {
        if (! $user->can('cortes.solicitar')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($caja) {
            return $user->cajas()->where('cajas.id', $caja->id)->exists();
        }

        return $user->cajas()->exists();
    }

    /**
     * RN-09: Determina si el usuario puede aprobar un corte de caja pendiente.
     */
    public function aprobar(User $user, CorteCaja $corte): bool
    {
        return $user->can('cortes.aprobar') && $corte->estado === EstadoCorte::Pendiente;
    }

    /**
     * RN-09: Determina si el usuario puede rechazar un corte de caja pendiente.
     */
    public function rechazar(User $user, CorteCaja $corte): bool
    {
        return $user->can('cortes.aprobar') && $corte->estado === EstadoCorte::Pendiente;
    }

    /**
     * RN-09: Determina si el usuario puede reabrir un corte de caja aprobado.
     */
    public function reabrir(User $user, CorteCaja $corte): bool
    {
        return $user->can('cortes.reabrir') && $corte->estado === EstadoCorte::Aprobado;
    }
}
