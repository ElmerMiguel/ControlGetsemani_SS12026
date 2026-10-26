<?php

namespace App\Models;

use App\Enums\EstadoBien;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Inventario
 *
 * Representa un bien o activo institucional perteneciente a un departamento.
 * Implementa RN3-01 (Inmutabilidad y SoftDeletes) y RN-16 (Auditable).
 */
class Inventario extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'inventarios';

    protected $fillable = [
        'departamento_id',
        'codigo',
        'nombre',
        'descripcion',
        'valor_estimado',
        'estado',
        'fecha_registro',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoBien::class,
            'valor_estimado' => 'decimal:2',
            'fecha_registro' => 'date',
        ];
    }

    /**
     * Departamento al que pertenece el bien institucional.
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    /**
     * Usuario que registró el bien.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Scope para aislar bienes accesibles para el usuario.
     * Admin: todos los bienes institucionales.
     * Tesorero: solo los bienes de departamentos donde tiene cajas asignadas.
     */
    public function scopeAccesiblesPara(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        $departamentoIds = $user->cajas()
            ->pluck('cajas.departamento_id')
            ->unique();

        return $query->whereIn($this->getTable().'.departamento_id', $departamentoIds);
    }
}
