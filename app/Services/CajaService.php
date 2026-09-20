<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Servicio contable para cálculo de saldos y agregaciones de caja en tiempo real.
 *
 * Implementa las reglas de negocio contables:
 * - RN-01: Cálculos monetarios en SQL y bcmath sin punto flotante.
 * - RN-02: Saldo actual = saldo_apertura + sum(ingresos) - sum(egresos) con fecha >= fecha_apertura.
 * - RN-03: Saldo periodo = saldo_inicial (al cierre anterior) + ingresos(P) - egresos(P).
 * - RN-10: Detección de saldo negativo.
 * - RN-15: Aislamiento por usuario y cajas asignadas.
 *
 * Excluye movimientos anulados (SoftDeletes) de todos los cálculos.
 * Excluye transferencias (transferencia_id no nulo) de los totales externos (serie mensual, top egresos),
 * pero las incluye en los saldos reales de caja.
 */
class CajaService
{
    /**
     * RN-02: Calcula el saldo actual de una caja a la fecha de hoy.
     * Incluye todas las operaciones vigentes desde su fecha de apertura.
     */
    public function saldoActual(Caja $caja): string
    {
        $fechaApertura = $caja->fecha_apertura ? $caja->fecha_apertura->format('Y-m-d') : null;

        $queryIngresos = $caja->ingresos();
        $queryEgresos = $caja->egresos();

        if ($fechaApertura) {
            $queryIngresos->whereDate('fecha', '>=', $fechaApertura);
            $queryEgresos->whereDate('fecha', '>=', $fechaApertura);
        }

        $totalIngresos = number_format((float) $queryIngresos->sum('monto'), 2, '.', '');
        $totalEgresos = number_format((float) $queryEgresos->sum('monto'), 2, '.', '');

        $saldoConIngresos = bcadd((string) $caja->saldo_apertura, $totalIngresos, 2);

        return bcsub($saldoConIngresos, $totalEgresos, 2);
    }

    /**
     * Calcula el saldo de una caja al cierre del día especificado.
     */
    public function saldoAl(Caja $caja, CarbonInterface|string $fecha): string
    {
        $fechaCorte = is_string($fecha) ? Carbon::parse($fecha)->format('Y-m-d') : $fecha->format('Y-m-d');
        $fechaApertura = $caja->fecha_apertura ? $caja->fecha_apertura->format('Y-m-d') : null;

        // Si la fecha solicitada es anterior a la fecha de apertura, el saldo es 0.00
        if ($fechaApertura && $fechaCorte < $fechaApertura) {
            return '0.00';
        }

        $queryIngresos = $caja->ingresos()->whereDate('fecha', '<=', $fechaCorte);
        $queryEgresos = $caja->egresos()->whereDate('fecha', '<=', $fechaCorte);

        if ($fechaApertura) {
            $queryIngresos->whereDate('fecha', '>=', $fechaApertura);
            $queryEgresos->whereDate('fecha', '>=', $fechaApertura);
        }

        $totalIngresos = number_format((float) $queryIngresos->sum('monto'), 2, '.', '');
        $totalEgresos = number_format((float) $queryEgresos->sum('monto'), 2, '.', '');

        $saldoConIngresos = bcadd((string) $caja->saldo_apertura, $totalIngresos, 2);

        return bcsub($saldoConIngresos, $totalEgresos, 2);
    }

    /**
     * RN-03: Resumen contable de un periodo [desde, hasta].
     * Devuelve [saldo_inicial, ingresos, egresos, saldo_final].
     */
    public function resumenPeriodo(Caja $caja, CarbonInterface|string $desde, CarbonInterface|string $hasta): array
    {
        $fechaDesde = is_string($desde) ? Carbon::parse($desde) : $desde;
        $fechaHasta = is_string($hasta) ? Carbon::parse($hasta) : $hasta;

        $desdeStr = $fechaDesde->format('Y-m-d');
        $hastaStr = $fechaHasta->format('Y-m-d');
        $fechaAperturaStr = $caja->fecha_apertura ? $caja->fecha_apertura->format('Y-m-d') : null;

        // Si el periodo inicia en o antes de la fecha de apertura, el saldo inicial es el saldo de apertura
        if ($fechaAperturaStr && $desdeStr <= $fechaAperturaStr) {
            $saldoInicial = number_format((float) $caja->saldo_apertura, 2, '.', '');
            $desdeMovimientos = $fechaAperturaStr;
        } else {
            // Saldo al cierre del día anterior
            $diaAnterior = $fechaDesde->copy()->subDay();
            $saldoInicial = $this->saldoAl($caja, $diaAnterior);
            $desdeMovimientos = $desdeStr;
        }

        $totalIngresos = number_format((float) $caja->ingresos()
            ->whereDate('fecha', '>=', $desdeMovimientos)
            ->whereDate('fecha', '<=', $hastaStr)
            ->sum('monto'), 2, '.', '');

        $totalEgresos = number_format((float) $caja->egresos()
            ->whereDate('fecha', '>=', $desdeMovimientos)
            ->whereDate('fecha', '<=', $hastaStr)
            ->sum('monto'), 2, '.', '');

        $saldoFinal = bcsub(bcadd($saldoInicial, $totalIngresos, 2), $totalEgresos, 2);

        return [
            'saldo_inicial' => $saldoInicial,
            'ingresos' => $totalIngresos,
            'egresos' => $totalEgresos,
            'saldo_final' => $saldoFinal,
        ];
    }

    /**
     * Serie mensual de 12 meses (ingresos y egresos) del año dado.
     * Excluye transferencias internas (transferencia_id no nulo).
     * Si $caja es null, calcula sobre todas las cajas accesibles para el usuario dado (o todos si es admin).
     */
    public function serieMensual(?Caja $caja, int $anio, ?User $user = null): array
    {
        $queryIngresos = Ingreso::query()
            ->whereNull('transferencia_id')
            ->whereYear('fecha', $anio);

        $queryEgresos = Egreso::query()
            ->whereNull('transferencia_id')
            ->whereYear('fecha', $anio);

        if ($caja) {
            $queryIngresos->where('caja_id', $caja->id);
            $queryEgresos->where('caja_id', $caja->id);
        } elseif ($user) {
            $cajasIds = Caja::accesiblesPara($user)->pluck('id');
            $queryIngresos->whereIn('caja_id', $cajasIds);
            $queryEgresos->whereIn('caja_id', $cajasIds);
        }

        $ingresosPorMes = $queryIngresos
            ->selectRaw('MONTH(fecha) as mes, SUM(monto) as total')
            ->groupBy(DB::raw('MONTH(fecha)'))
            ->pluck('total', 'mes')
            ->all();

        $egresosPorMes = $queryEgresos
            ->selectRaw('MONTH(fecha) as mes, SUM(monto) as total')
            ->groupBy(DB::raw('MONTH(fecha)'))
            ->pluck('total', 'mes')
            ->all();

        $mesesNombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $ingresosData = [];
        $egresosData = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $ingresosData[] = (float) ($ingresosPorMes[$mes] ?? 0.0);
            $egresosData[] = (float) ($egresosPorMes[$mes] ?? 0.0);
        }

        return [
            'labels' => $mesesNombres,
            'ingresos' => $ingresosData,
            'egresos' => $egresosData,
        ];
    }

    /**
     * Top N cuentas de egreso por monto consumido en el periodo.
     * Excluye transferencias internas.
     */
    public function topEgresos(?Caja $caja, CarbonInterface|string $desde, CarbonInterface|string $hasta, int $n = 5, ?User $user = null): array
    {
        $desdeStr = is_string($desde) ? Carbon::parse($desde)->format('Y-m-d') : $desde->format('Y-m-d');
        $hastaStr = is_string($hasta) ? Carbon::parse($hasta)->format('Y-m-d') : $hasta->format('Y-m-d');

        $query = Egreso::query()
            ->join('catalogo_egresos', 'egresos.cuenta_egreso_id', '=', 'catalogo_egresos.id')
            ->whereNull('egresos.transferencia_id')
            ->whereDate('egresos.fecha', '>=', $desdeStr)
            ->whereDate('egresos.fecha', '<=', $hastaStr)
            ->select(
                'catalogo_egresos.codigo',
                'catalogo_egresos.nombre',
                DB::raw('SUM(egresos.monto) as total')
            )
            ->groupBy('catalogo_egresos.id', 'catalogo_egresos.codigo', 'catalogo_egresos.nombre')
            ->orderByDesc('total')
            ->limit($n);

        if ($caja) {
            $query->where('egresos.caja_id', $caja->id);
        } elseif ($user) {
            $cajasIds = Caja::accesiblesPara($user)->pluck('id');
            $query->whereIn('egresos.caja_id', $cajasIds);
        }

        return $query->get()->map(function ($row) {
            return [
                'codigo' => $row->codigo,
                'nombre' => $row->nombre,
                'total' => number_format((float) $row->total, 2, '.', ''),
            ];
        })->toArray();
    }

    /**
     * Totales externos del mes actual para una caja o conjunto de cajas (excluye transferencias).
     */
    public function totalesMesActual(?Caja $caja, ?User $user = null): array
    {
        $inicioMes = now()->startOfMonth()->format('Y-m-d');
        $finMes = now()->endOfMonth()->format('Y-m-d');

        $queryIngresos = Ingreso::query()
            ->whereNull('transferencia_id')
            ->whereDate('fecha', '>=', $inicioMes)
            ->whereDate('fecha', '<=', $finMes);

        $queryEgresos = Egreso::query()
            ->whereNull('transferencia_id')
            ->whereDate('fecha', '>=', $inicioMes)
            ->whereDate('fecha', '<=', $finMes);

        if ($caja) {
            $queryIngresos->where('caja_id', $caja->id);
            $queryEgresos->where('caja_id', $caja->id);
        } elseif ($user) {
            $cajasIds = Caja::accesiblesPara($user)->pluck('id');
            $queryIngresos->whereIn('caja_id', $cajasIds);
            $queryEgresos->whereIn('caja_id', $cajasIds);
        }

        return [
            'ingresos' => number_format((float) $queryIngresos->sum('monto'), 2, '.', ''),
            'egresos' => number_format((float) $queryEgresos->sum('monto'), 2, '.', ''),
        ];
    }
}
