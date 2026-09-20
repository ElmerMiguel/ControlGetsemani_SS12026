<?php

namespace App\Policies;

use App\Models\Egreso;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * EgresoPolicy
 *
 * Implementa autorización en tres capas (ARQUITECTURA §7.2):
 * 1. Permiso Spatie (egresos.ver, egresos.crear, egresos.editar, egresos.anular).
 * 2. Acceso a la caja asociada (RN-15 / caja_user o rol con permiso cajas.gestionar).
 * 3. Estado no bloqueado (RN-05, RN-06: nadie edita ni anula movimientos en periodos con corte).
 */
class EgresoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('egresos.ver');
    }

    public function view(User $user, Egreso $egreso): Response
    {
        if (! $user->can('egresos.ver')) {
            return Response::deny('No tiene permiso para consultar egresos.');
        }

        if (! $this->tieneAccesoACaja($user, $egreso->caja_id)) {
            return Response::deny('No tiene acceso a la caja asociada a este egreso.');
        }

        return Response::allow();
    }

    public function create(User $user): bool
    {
        return $user->can('egresos.crear');
    }

    public function update(User $user, Egreso $egreso): Response
    {
        if (! $user->can('egresos.editar')) {
            return Response::deny('No tiene permiso para editar egresos.');
        }

        if (! $this->tieneAccesoACaja($user, $egreso->caja_id)) {
            return Response::deny('No tiene acceso a la caja asociada a este egreso.');
        }

        if ($egreso->esBloqueado()) {
            return Response::deny('No es posible modificar un egreso en un periodo bloqueado por corte de caja.');
        }

        return Response::allow();
    }

    public function anular(User $user, Egreso $egreso): Response
    {
        if (! $user->can('egresos.anular')) {
            return Response::deny('No tiene permiso para anular egresos.');
        }

        if (! $this->tieneAccesoACaja($user, $egreso->caja_id)) {
            return Response::deny('No tiene acceso a la caja asociada a este egreso.');
        }

        if ($egreso->esBloqueado()) {
            return Response::deny('No es posible anular un egreso en un periodo bloqueado por corte de caja.');
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
