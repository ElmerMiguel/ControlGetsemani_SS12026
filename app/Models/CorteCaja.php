<?php

namespace App\Models;

use App\Enums\EstadoCorte;
use App\Models\Concerns\AccesiblePorCajas;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorteCaja extends Model
{
    use AccesiblePorCajas, Auditable, HasFactory;

    protected $table = 'cortes_caja';

    protected $fillable = [
        'caja_id',
        'periodo_inicio',
        'periodo_fin',
        'estado',
        'saldo_inicial',
        'total_ingresos',
        'total_egresos',
        'saldo_final',
        'solicitado_por',
        'solicitado_at',
        'revisado_por',
        'revisado_at',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fin' => 'date',
            'estado' => EstadoCorte::class,
            'saldo_inicial' => 'decimal:2',
            'total_ingresos' => 'decimal:2',
            'total_egresos' => 'decimal:2',
            'saldo_final' => 'decimal:2',
            'solicitado_at' => 'datetime',
            'revisado_at' => 'datetime',
        ];
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
