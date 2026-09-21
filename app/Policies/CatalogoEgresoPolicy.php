<?php

namespace App\Policies;

use App\Models\CatalogoEgreso;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * CatalogoEgresoPolicy
 *
 * Implementa RN-12: Lectura con catalogos.ver y escritura con catalogos.gestionar.
 * Cuentas es_transferencia no son editables.
 */
class CatalogoEgresoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalogos.ver') || $user->can('catalogos.gestionar');
    }

    public function view(User $user, CatalogoEgreso $catalogoEgreso): bool
    {
        return $user->can('catalogos.ver') || $user->can('catalogos.gestionar');
    }

    public function create(User $user): bool
    {
        return $user->can('catalogos.gestionar');
    }

    public function update(User $user, CatalogoEgreso $catalogoEgreso): Response
    {
        if (! $user->can('catalogos.gestionar')) {
            return Response::deny('No tiene permisos para gestionar catálogos.');
        }

        if ($catalogoEgreso->es_transferencia) {
            return Response::deny('Las cuentas de transferencia del sistema son protegidas y no pueden modificarse.');
        }

        return Response::allow();
    }

    public function toggleEstado(User $user, CatalogoEgreso $catalogoEgreso): Response
    {
        if (! $user->can('catalogos.gestionar')) {
            return Response::deny('No tiene permisos para gestionar catálogos.');
        }

        if ($catalogoEgreso->es_transferencia) {
            return Response::deny('Las cuentas de transferencia del sistema son protegidas y no pueden desactivarse.');
        }

        return Response::allow();
    }
}
