<?php

namespace App\Policies;

use App\Models\Departamento;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * DepartamentoPolicy
 *
 * Implementa RN-13: Autorización y reglas de departamentos.
 * Un departamento con cajas activas no se puede desactivar.
 */
class DepartamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('departamentos.gestionar');
    }

    public function view(User $user, Departamento $departamento): bool
    {
        return $user->can('departamentos.gestionar');
    }

    public function create(User $user): bool
    {
        return $user->can('departamentos.gestionar');
    }

    public function update(User $user, Departamento $departamento): bool
    {
        return $user->can('departamentos.gestionar');
    }

    /**
     * RN-13: Un departamento con cajas activas no se puede desactivar.
     */
    public function toggleEstado(User $user, Departamento $departamento): Response
    {
        if (! $user->can('departamentos.gestionar')) {
            return Response::deny('No tiene permisos para gestionar departamentos.');
        }

        // Si se intenta desactivar
        if ($departamento->activo && $departamento->cajas()->where('activa', true)->exists()) {
            return Response::deny('No es posible desactivar un departamento que contiene cajas activas.');
        }

        return Response::allow();
    }
}
