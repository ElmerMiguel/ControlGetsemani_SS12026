<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait AccesiblePorCajas
 *
 * Implementa RN-15: Aislamiento de información por caja.
 * Si el usuario tiene el rol 'admin', no filtra (acceso completo a todas las cajas).
 * Si no es admin, restringe la consulta a los registros cuya 'caja_id' esté asignada al usuario.
 */
trait AccesiblePorCajas
{
    /**
     * Scope para limitar consultas a las cajas a las que tiene acceso el usuario.
     */
    public function scopeAccesiblesPara(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        $cajaIds = $user->cajas()->pluck('cajas.id');

        return $query->whereIn($this->getTable().'.caja_id', $cajaIds);
    }
}
