<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * UserPolicy
 *
 * Implementa RN-14: Gestión de usuarios y restricciones de seguridad.
 * - Solo usuarios con permiso 'usuarios.gestionar' pueden acceder al módulo.
 * - Nadie puede desactivarse a sí mismo.
 * - El sistema no puede quedarse sin administradores activos.
 */
class UserPolicy
{
    /**
     * Determina si el usuario puede ver la lista de usuarios.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.gestionar');
    }

    /**
     * Determina si el usuario puede ver el detalle de un usuario.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('usuarios.gestionar');
    }

    /**
     * Determina si el usuario puede crear nuevos usuarios.
     */
    public function create(User $user): bool
    {
        return $user->can('usuarios.gestionar');
    }

    /**
     * Determina si el usuario puede actualizar un usuario.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can('usuarios.gestionar');
    }

    /**
     * Determina si el usuario puede cambiar el estado (activar/desactivar).
     * RN-14: Nadie puede desactivarse a sí mismo ni dejar el sistema sin un admin activo.
     */
    public function toggleEstado(User $user, User $model): Response
    {
        if (! $user->can('usuarios.gestionar')) {
            return Response::deny('No tiene permisos para gestionar usuarios.');
        }

        // Si se intenta desactivar
        if ($model->activo) {
            // Nadie puede desactivarse a sí mismo
            if ($user->id === $model->id) {
                return Response::deny('No puedes desactivar tu propia cuenta de usuario.');
            }

            // Si el usuario a desactivar es administrador, validar que haya al menos otro admin activo
            if ($model->hasRole('admin')) {
                $otrosAdminsActivos = User::role('admin')
                    ->where('activo', true)
                    ->where('id', '!=', $model->id)
                    ->count();

                if ($otrosAdminsActivos === 0) {
                    return Response::deny('No es posible desactivar al único administrador activo del sistema.');
                }
            }
        }

        return Response::allow();
    }

    /**
     * Determina si el usuario puede regenerar una contraseña temporal.
     */
    public function resetPassword(User $user, User $model): bool
    {
        return $user->can('usuarios.gestionar');
    }
}
