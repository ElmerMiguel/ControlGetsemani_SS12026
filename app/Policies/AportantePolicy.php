<?php

namespace App\Policies;

use App\Models\Aportante;
use App\Models\User;

/**
 * AportantePolicy
 *
 * Gestión de aportantes con permiso 'aportantes.gestionar' (disponible para admin y tesoreros).
 */
class AportantePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('aportantes.gestionar');
    }

    public function view(User $user, Aportante $aportante): bool
    {
        return $user->can('aportantes.gestionar');
    }

    public function create(User $user): bool
    {
        return $user->can('aportantes.gestionar');
    }

    public function update(User $user, Aportante $aportante): bool
    {
        return $user->can('aportantes.gestionar');
    }

    public function toggleEstado(User $user, Aportante $aportante): bool
    {
        return $user->can('aportantes.gestionar');
    }
}
