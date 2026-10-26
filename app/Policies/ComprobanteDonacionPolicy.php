<?php

namespace App\Policies;

use App\Models\ComprobanteDonacion;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * ComprobanteDonacionPolicy
 *
 * Implementa control de acceso estricto y prevención de IDOR:
 * - Requiere pertenencia institucional a la caja donde se registró el ingreso.
 * - Administradores generales poseen acceso irrestricto de consulta y emisión.
 */
class ComprobanteDonacionPolicy
{
    /**
     * Determina si el usuario puede generar un comprobante para el ingreso dado.
     */
    public function generar(User $user, Ingreso $ingreso): Response
    {
        if (! $user->can('ingresos.crear') && ! $user->can('ingresos.ver')) {
            return Response::deny('No tiene permisos para emitir comprobantes de donación.');
        }

        if (! $this->tieneAccesoACaja($user, $ingreso->caja_id)) {
            return Response::deny('No tiene acceso a la caja asociada a este ingreso (prevención IDOR).');
        }

        return Response::allow();
    }

    /**
     * Determina si el usuario puede ver o descargar el comprobante.
     */
    public function descargar(User $user, ComprobanteDonacion $comprobante): Response
    {
        if (! $user->can('ingresos.ver')) {
            return Response::deny('No tiene permisos para consultar comprobantes de donación.');
        }

        $cajaId = $comprobante->ingreso?->caja_id;
        if ($cajaId && ! $this->tieneAccesoACaja($user, $cajaId)) {
            return Response::deny('No tiene acceso a la caja asociada a este comprobante (prevención IDOR).');
        }

        return Response::allow();
    }

    /**
     * Determina si el usuario puede enviar por correo electrónico el comprobante.
     */
    public function enviar(User $user, ComprobanteDonacion $comprobante): Response
    {
        if (! $user->can('ingresos.ver') && ! $user->can('ingresos.crear')) {
            return Response::deny('No tiene permisos para enviar comprobantes por correo.');
        }

        $cajaId = $comprobante->ingreso?->caja_id;
        if ($cajaId && ! $this->tieneAccesoACaja($user, $cajaId)) {
            return Response::deny('No tiene acceso a la caja asociada a este comprobante (prevención IDOR).');
        }

        return Response::allow();
    }

    /**
     * Verifica si el usuario tiene acceso a la caja indicada (RN-15).
     */
    private function tieneAccesoACaja(User $user, int $cajaId): bool
    {
        if ($user->can('cajas.gestionar') || $user->hasRole('Administrador General') || $user->hasRole('admin')) {
            return true;
        }

        return $user->cajas()->where('cajas.id', $cajaId)->exists();
    }
}
