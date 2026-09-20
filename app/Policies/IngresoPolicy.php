<?php

namespace App\Policies;

use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * IngresoPolicy
 *
 * Implementa autorización en tres capas (ARQUITECTURA §7.2):
 * 1. Permiso Spatie (ingresos.ver, ingresos.crear, ingresos.editar, ingresos.anular).
 * 2. Acceso a la caja asociada (RN-15 / caja_user o rol con permiso cajas.gestionar).
 * 3. Estado no bloqueado (RN-05, RN-06: nadie edita ni anula movimientos en periodos con corte).
 */
class IngresoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ingresos.ver');
    }

    public function view(User $user, Ingreso $ingreso): Response
    {
        if (! $user->can('ingresos.ver')) {
            return Response::deny('No tiene permiso para consultar ingresos.');
        }

        if (! $this->tieneAccesoACaja($user, $ingreso->caja_id)) {
            return Response::deny('No tiene acceso a la caja asociada a este ingreso.');
        }

        return Response::allow();
    }

    public function create(User $user): bool
    {
        return $user->can('ingresos.crear');
    }

    public function update(User $user, Ingreso $ingreso): Response
    {
        if (! $user->can('ingresos.editar')) {
            return Response::deny('No tiene permiso para editar ingresos.');
        }

        if (! $this->tieneAccesoACaja($user, $ingreso->caja_id)) {
            return Response::deny('No tiene acceso a la caja asociada a este ingreso.');
        }

        if ($ingreso->esBloqueado()) {
            return Response::deny('No es posible modificar un ingreso en un periodo bloqueado por corte de caja.');
        }

        return Response::allow();
    }

    public function anular(User $user, Ingreso $ingreso): Response
    {
        if (! $user->can('ingresos.anular')) {
            return Response::deny('No tiene permiso para anular ingresos.');
        }

        if (! $this->tieneAccesoACaja($user, $ingreso->caja_id)) {
            return Response::deny('No tiene acceso a la caja asociada a este ingreso.');
        }

        if ($ingreso->esBloqueado()) {
            return Response::deny('No es posible anular un ingreso en un periodo bloqueado por corte de caja.');
        }

        return Response::allow();
    }

    /**
     * Verifica si el usuario tiene acceso a la caja indicada (RN-15).
     */
    private function tieneAccesoACaja(User $user, int $cajaId): bool
    {
        if ($user->can('cajas.gestionar') || $user->hasRole('admin')) {
            return true;
        }

        return $user->cajas()->where('cajas.id', $cajaId)->exists();
    }
}
