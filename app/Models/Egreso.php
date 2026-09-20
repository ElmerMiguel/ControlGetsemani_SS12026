<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Egreso extends Model
{
    use HasFactory, SoftDeletes;

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
}
