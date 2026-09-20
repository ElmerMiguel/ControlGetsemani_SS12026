<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * BitacoraService
 *
 * Implementa RN-16: Bitácora de auditoría inmutable del sistema.
 * Registra acciones CRUD, eventos de autenticación y transacciones del negocio,
 * garantizando la exclusión de contraseñas y datos sensibles.
 */
class BitacoraService
{
    /**
     * Registra un movimiento o cambio en la bitácora de auditoría.
     */
    public function registrar(
        string $accion,
        ?Model $modelo,
        string $descripcion,
        ?array $antes = null,
        ?array $despues = null,
        ?int $usuarioId = null
    ): ?Bitacora {
        if (! config('auditoria.activa', true)) {
            return null;
        }

        $excluidos = config('auditoria.campos_excluidos', [
            'password',
            'remember_token',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        if ($antes !== null) {
            $antes = array_diff_key($antes, array_flip($excluidos));
        }

        if ($despues !== null) {
            $despues = array_diff_key($despues, array_flip($excluidos));
        }

        $tabla = $modelo ? $modelo->getTable() : null;
        $registroId = $modelo ? $modelo->getKey() : null;

        $usuarioId = $usuarioId ?? Auth::id();
        if (! $usuarioId && $modelo instanceof User) {
            $usuarioId = $modelo->id;
        }
        $ip = request()->ip() ?? '127.0.0.1';
        $userAgent = request()->userAgent() ?? 'Sistema / Consola';

        return Bitacora::create([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'registro_id' => $registroId,
            'descripcion' => $descripcion,
            'datos_antes' => ! empty($antes) ? $antes : null,
            'datos_despues' => ! empty($despues) ? $despues : null,
            'ip' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
            'fecha_hora' => now(),
        ]);
    }

    /**
     * Atajo para registrar eventos de negocio sin estado previo o posterior.
     */
    public function evento(string $accion, ?Model $modelo, string $descripcion): ?Bitacora
    {
        return $this->registrar($accion, $modelo, $descripcion, null, null);
    }
}
