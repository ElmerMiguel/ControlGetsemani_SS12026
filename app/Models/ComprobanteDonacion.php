<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprobanteDonacion extends Model
{
    use Auditable, HasFactory;

    protected $table = 'comprobantes_donacion';

    protected $fillable = [
        'ingreso_id',
        'aportante_id',
        'numero',
        'generado_en',
        'generado_por',
        'enviado_en',
        'ruta_pdf',
    ];

    protected function casts(): array
    {
        return [
            'generado_en' => 'datetime',
            'enviado_en' => 'datetime',
        ];
    }

    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id')->withTrashed();
    }

    public function aportante(): BelongsTo
    {
        return $this->belongsTo(Aportante::class, 'aportante_id');
    }

    public function generador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    /**
     * Scope para filtrar comprobantes según el acceso a la caja del ingreso asociado.
     */
    public function scopeAccesiblesPara(Builder $query, User $user): Builder
    {
        if ($user->hasRole('Administrador General')) {
            return $query;
        }

        return $query->whereHas('ingreso.caja', function (Builder $q) use ($user) {
            $q->accesiblesPara($user);
        });
    }
}
