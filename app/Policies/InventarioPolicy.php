<?php

namespace App\Policies;

use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\User;

/**
 * Policy de Inventario
 *
 * Implementa aislamiento inter-departamental (RN-15 / RN3-01):
 * - Administrador: acceso total a inventario institucional.
 * - Tesorero: únicamente bienes de departamentos donde tiene cajas asignadas.
 */
class InventarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inventario.ver');
    }

    public function view(User $user, Inventario $inventario): bool
    {
        if (! $user->hasPermissionTo('inventario.ver')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $this->perteneceADepartamentoAsignado($user, $inventario->departamento_id);
    }

    public function create(User $user, ?Departamento $departamento = null): bool
    {
        if (! $user->hasPermissionTo('inventario.crear')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($departamento) {
            return $this->perteneceADepartamentoAsignado($user, $departamento->id);
        }

        return $user->cajas()->exists();
    }

    public function update(User $user, Inventario $inventario): bool
    {
        if (! $user->hasPermissionTo('inventario.editar')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $this->perteneceADepartamentoAsignado($user, $inventario->departamento_id);
    }

    public function delete(User $user, Inventario $inventario): bool
    {
        if (! $user->hasPermissionTo('inventario.eliminar')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $this->perteneceADepartamentoAsignado($user, $inventario->departamento_id);
    }

    public function cambiarEstado(User $user, Inventario $inventario): bool
    {
        if (! $user->hasPermissionTo('inventario.cambiar_estado')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $this->perteneceADepartamentoAsignado($user, $inventario->departamento_id);
    }

    private function perteneceADepartamentoAsignado(User $user, int $departamentoId): bool
    {
        return $user->cajas()
            ->where('cajas.departamento_id', $departamentoId)
            ->exists();
    }
}
