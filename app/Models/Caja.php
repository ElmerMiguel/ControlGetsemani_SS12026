<?php

namespace App\Models;

use App\Enums\MedioCaja;
use App\Models\Concerns\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    use Auditable, HasFactory;

    protected $table = 'cajas';

    protected $fillable = [
        'departamento_id',
        'nombre',
        'codigo',
        'medio',
        'saldo_apertura',
        'fecha_apertura',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'medio' => MedioCaja::class,
            'saldo_apertura' => 'decimal:2',
            'fecha_apertura' => 'date',
            'activa' => 'boolean',
        ];
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'caja_user', 'caja_id', 'user_id');
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'caja_id');
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class, 'caja_id');
    }

    public function cortesCaja(): HasMany
    {
        return $this->hasMany(CorteCaja::class, 'caja_id');
    }

    public function transferenciasOrigen(): HasMany
    {
        return $this->hasMany(Transferencia::class, 'caja_origen_id');
    }

    public function transferenciasDestino(): HasMany
    {
        return $this->hasMany(Transferencia::class, 'caja_destino_id');
    }

    public function tesoreros(): BelongsToMany
    {
        return $this->usuarios();
    }

    /**
     * Determina si la caja ya cuenta con movimientos contables o cortes registrados.
     * Si es true, saldo_apertura y fecha_apertura son inmutables.
     */
    public function tieneMovimientos(): bool
    {
        return $this->ingresos()->exists()
            || $this->egresos()->exists()
            || $this->cortesCaja()->exists()
            || $this->transferenciasOrigen()->exists()
            || $this->transferenciasDestino()->exists();
    }

    /**
     * RN-05: Determina si la fecha indicada se encuentra dentro de un periodo bloqueado.
     * Existe un corte de esa caja en estado 'pendiente' o 'aprobado' con periodo_inicio <= fecha <= periodo_fin.
     */
    public function estaBloqueada($fecha): bool
    {
        $f = is_string($fecha) ? Carbon::parse($fecha)->format('Y-m-d') : $fecha->format('Y-m-d');

        return $this->cortesCaja()
            ->whereIn('estado', ['pendiente', 'aprobado'])
            ->whereDate('periodo_inicio', '<=', $f)
            ->whereDate('periodo_fin', '>=', $f)
            ->exists();
    }

    /**
     * RN-02: Saldo actual de una caja = saldo_apertura + sum(ingresos vigentes) - sum(egresos vigentes),
     * con fecha >= fecha_apertura. Se calcula al consultar; no se guarda.
     */
    public function calcularSaldoActual(): string
    {
        $totalIngresos = (string) ($this->ingresos()
            ->whereDate('fecha', '>=', $this->fecha_apertura)
            ->sum('monto') ?? '0.00');

        $totalEgresos = (string) ($this->egresos()
            ->whereDate('fecha', '>=', $this->fecha_apertura)
            ->sum('monto') ?? '0.00');

        $saldoConIngresos = bcadd((string) $this->saldo_apertura, $totalIngresos, 2);

        return bcsub($saldoConIngresos, $totalEgresos, 2);
    }
}
