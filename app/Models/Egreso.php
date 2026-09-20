<?php

namespace App\Models;

use App\Models\Concerns\AccesiblePorCajas;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Egreso extends Model
{
    use AccesiblePorCajas, Auditable, HasFactory, SoftDeletes;

    protected $table = 'egresos';

    protected $fillable = [
        'caja_id',
        'fecha',
        'cuenta_egreso_id',
        'monto',
        'descripcion',
        'referencia',
        'usuario_id',
        'transferencia_id',
        'anulado_por',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CatalogoEgreso::class, 'cuenta_egreso_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(Transferencia::class, 'transferencia_id');
    }

    public function anulador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    /**
     * RN-05 / RN-06: Verifica si el egreso pertenece a un periodo bloqueado por corte.
     */
    public function esBloqueado(): bool
    {
        return $this->caja?->estaBloqueada($this->fecha) ?? false;
    }
}
