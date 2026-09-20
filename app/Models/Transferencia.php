<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transferencia extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'transferencias';

    protected $fillable = [
        'caja_origen_id',
        'caja_destino_id',
        'fecha',
        'monto',
        'concepto',
        'usuario_id',
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

    public function cajaOrigen(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_origen_id');
    }

    public function cajaDestino(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function anulador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'transferencia_id');
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class, 'transferencia_id');
    }
}
