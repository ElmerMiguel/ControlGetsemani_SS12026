<?php

namespace App\Models;

use App\Services\BitacoraService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Modelo Bitacora
 *
 * Implementa RN-16: Bitácora de auditoría inmutable.
 * Lanza LogicException ante cualquier intento de edición o eliminación.
 */
class Bitacora extends Model
{
    use HasFactory;

    protected $table = 'bitacora_auditoria';

    public const CREATED_AT = 'fecha_hora';

    public const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id',
        'accion',
        'tabla_afectada',
        'registro_id',
        'descripcion',
        'datos_antes',
        'datos_despues',
        'ip',
        'user_agent',
        'fecha_hora',
    ];

    protected function casts(): array
    {
        return [
            'datos_antes' => 'array',
            'datos_despues' => 'array',
            'fecha_hora' => 'datetime',
        ];
    }

    /**
     * Reglas de inmutabilidad del modelo.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Los registros de bitácora son inmutables y no pueden modificarse.');
        });

        static::deleting(function () {
            throw new LogicException('Los registros de bitácora son inmutables y no pueden eliminarse.');
        });
    }

    /**
     * Bloqueo explícito de actualización.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('Los registros de bitácora son inmutables y no pueden modificarse.');
    }

    /**
     * Bloqueo explícito de eliminación.
     */
    public function delete(): ?bool
    {
        throw new LogicException('Los registros de bitácora son inmutables y no pueden eliminarse.');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Atajo estático para registrar eventos de negocio en la bitácora (RN-16).
     */
    public static function evento(string $accion, ?Model $modelo, string $descripcion): ?self
    {
        return app(BitacoraService::class)->evento($accion, $modelo, $descripcion);
    }
}
