<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;

/**
 * CajaPolicy
 *
 * Implementa autorización para gestión y consulta de cajas (RN-13, RN-15).
 */
class CajaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cajas.gestionar') || $user->hasRole('tesorero');
    }

    public function view(User $user, Caja $caja): bool
    {
        if ($user->can('cajas.gestionar')) {
            return true;
        }

        return $user->cajas()->where('cajas.id', $caja->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('cajas.gestionar');
    }

    public function update(User $user, Caja $caja): bool
    {
        return $user->can('cajas.gestionar');
    }

    public function toggleEstado(User $user, Caja $caja): bool
    {
        return $user->can('cajas.gestionar');
    }
}
