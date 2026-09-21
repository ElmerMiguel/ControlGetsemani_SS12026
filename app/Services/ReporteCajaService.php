<?php

namespace App\Services;

use App\Models\Caja;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * ReporteCajaService
 *
 * Fuente ÚNICA de datos para los reportes de caja en HTML, PDF y Excel.
 * Implementa:
 * - RN-02: Cálculo de saldos acumulados en tiempo real.
 * - RN-03: Resumen contable por periodo (saldo_inicial, ingresos, egresos, saldo_final).
 * - RN-11: Presentación de transferencias internas por separado de los flujos ordinarios.
 * - RN-15: Respeto al modelo de aislamiento de cajas.
 * - ARQUITECTURA §11: Estructura unificada de cabecera, resumen, desglose por cuenta,
 *   ingresos por mes, matriz egresos cuenta × mes, transferencias y firmas.
 */
class ReporteCajaService
{
    public function __construct(
        protected CajaService $cajaService
    ) {}

    /**
     * Genera la estructura completa de datos del reporte de caja para un período dado.
     *
     * @return array<string, mixed>
     */
    public function generar(Caja $caja, CarbonInterface|string $desde, CarbonInterface|string $hasta): array
    {
        $fechaDesde = is_string($desde) ? Carbon::parse($desde) : $desde->copy();
        $fechaHasta = is_string($hasta) ? Carbon::parse($hasta) : $hasta->copy();

        $desdeStr = $fechaDesde->format('Y-m-d');
        $hastaStr = $fechaHasta->format('Y-m-d');

        $caja->loadMissing('departamento');

        // 1. Cabecera institucional
        $cabecera = [
            'entidad' => 'Iglesia de Dios Pentecostal del Evangelio Completo "Getsemaní"',
            'departamento' => $caja->departamento?->nombre ?? 'Sin departamento',
            'caja' => $caja->nombre,
            'caja_codigo' => $caja->codigo,
            'periodo_desde' => $desdeStr,
            'periodo_hasta' => $hastaStr,
            'periodo_texto' => 'Del '.$fechaDesde->format('d/m/Y').' al '.$fechaHasta->format('d/m/Y'),
            'moneda' => 'Quetzales (Q)',
            'generado_el' => now()->format('d/m/Y H:i'),
        ];

        // 2. Resumen contable (RN-03 y RN-11)
        $resumenBase = $this->cajaService->resumenPeriodo($caja, $fechaDesde, $fechaHasta);
        $saldoInicial = $resumenBase['saldo_inicial'];

        // Ingresos ordinarios vs transferencias recibidas
        $ingresosOrdinarios = number_format((float) $caja->ingresos()
            ->whereNull('transferencia_id')
            ->whereDate('fecha', '>=', $desdeStr)
            ->whereDate('fecha', '<=', $hastaStr)
            ->sum('monto'), 2, '.', '');

        $transferenciasRecibidas = number_format((float) $caja->ingresos()
            ->whereNotNull('transferencia_id')
            ->whereDate('fecha', '>=', $desdeStr)
            ->whereDate('fecha', '<=', $hastaStr)
            ->sum('monto'), 2, '.', '');

        // Egresos ordinarios vs transferencias enviadas
        $egresosOrdinarios = number_format((float) $caja->egresos()
            ->whereNull('transferencia_id')
            ->whereDate('fecha', '>=', $desdeStr)
            ->whereDate('fecha', '<=', $hastaStr)
            ->sum('monto'), 2, '.', '');

        $transferenciasEnviadas = number_format((float) $caja->egresos()
            ->whereNotNull('transferencia_id')
            ->whereDate('fecha', '>=', $desdeStr)
            ->whereDate('fecha', '<=', $hastaStr)
            ->sum('monto'), 2, '.', '');

        $totalIngresos = bcadd($ingresosOrdinarios, $transferenciasRecibidas, 2);
        $totalEgresos = bcadd($egresosOrdinarios, $transferenciasEnviadas, 2);
        $saldoFinal = bcsub(bcadd($saldoInicial, $totalIngresos, 2), $totalEgresos, 2);
        $netoPeriodo = bcsub($totalIngresos, $totalEgresos, 2);

        $resumen = [
            'saldo_inicial' => $saldoInicial,
            'ingresos_ordinarios' => $ingresosOrdinarios,
            'transferencias_recibidas' => $transferenciasRecibidas,
            'total_ingresos' => $totalIngresos,
            'egresos_ordinarios' => $egresosOrdinarios,
            'transferencias_enviadas' => $transferenciasEnviadas,
            'total_egresos' => $totalEgresos,
            'neto_periodo' => $netoPeriodo,
            'saldo_final' => $saldoFinal,
        ];

        // 3. Meses comprendidos en el rango
        $nombresMeses = [
            '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
        ];
        $meses = [];
        $cursor = $fechaDesde->copy()->startOfMonth();
        $finMes = $fechaHasta->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($finMes)) {
            $clave = $cursor->format('Y-m');
            $meses[$clave] = [
                'clave' => $clave,
                'nombre' => $nombresMeses[$cursor->format('m')].' '.$cursor->format('Y'),
                'mes_num' => $cursor->format('m'),
                'anio' => $cursor->format('Y'),
            ];
            $cursor->addMonth();
        }

        // 4. Ingresos por cuenta (excluyendo cuenta de transferencia 900)
        $ingresosCuentasRaw = DB::table('ingresos as i')
            ->join('catalogo_ingresos as c', 'c.id', '=', 'i.cuenta_ingreso_id')
            ->where('i.caja_id', $caja->id)
            ->whereNull('i.deleted_at')
            ->whereNull('i.transferencia_id')
            ->whereDate('i.fecha', '>=', $desdeStr)
            ->whereDate('i.fecha', '<=', $hastaStr)
            ->select('c.id', 'c.codigo', 'c.nombre', DB::raw('SUM(i.monto) as total'))
            ->groupBy('c.id', 'c.codigo', 'c.nombre')
            ->orderBy('c.codigo', 'asc')
            ->get();

        $ingresosPorCuenta = [];
        foreach ($ingresosCuentasRaw as $row) {
            $totalCuenta = number_format((float) $row->total, 2, '.', '');
            $porcentaje = ((float) $ingresosOrdinarios > 0)
                ? round(((float) $totalCuenta / (float) $ingresosOrdinarios) * 100, 2)
                : 0.0;

            $ingresosPorCuenta[] = [
                'id' => $row->id,
                'codigo' => $row->codigo,
                'nombre' => $row->nombre,
                'total' => $totalCuenta,
                'porcentaje' => $porcentaje,
            ];
        }

        // 5. Ingresos por mes (ordinarios)
        $ingresosMesRaw = DB::table('ingresos')
            ->where('caja_id', $caja->id)
            ->whereNull('deleted_at')
            ->whereNull('transferencia_id')
            ->whereDate('fecha', '>=', $desdeStr)
            ->whereDate('fecha', '<=', $hastaStr)
            ->select(DB::raw("DATE_FORMAT(fecha, '%Y-%m') as mes"), DB::raw('SUM(monto) as total'))
            ->groupBy(DB::raw("DATE_FORMAT(fecha, '%Y-%m')"))
            ->pluck('total', 'mes')
            ->toArray();

        $ingresosPorMes = [];
        foreach ($meses as $clave => $infoMes) {
            $montoMes = isset($ingresosMesRaw[$clave])
                ? number_format((float) $ingresosMesRaw[$clave], 2, '.', '')
                : '0.00';

            $ingresosPorMes[$clave] = [
                'clave' => $clave,
                'nombre' => $infoMes['nombre'],
                'total' => $montoMes,
            ];
        }

        // 6. Egresos por cuenta (excluyendo cuenta 900)
        $egresosCuentasRaw = DB::table('egresos as e')
            ->join('catalogo_egresos as c', 'c.id', '=', 'e.cuenta_egreso_id')
            ->where('e.caja_id', $caja->id)
            ->whereNull('e.deleted_at')
            ->whereNull('e.transferencia_id')
            ->whereDate('e.fecha', '>=', $desdeStr)
            ->whereDate('e.fecha', '<=', $hastaStr)
            ->select('c.id', 'c.codigo', 'c.nombre', DB::raw('SUM(e.monto) as total'))
            ->groupBy('c.id', 'c.codigo', 'c.nombre')
            ->orderBy('c.codigo', 'asc')
            ->get();

        $egresosPorCuenta = [];
        foreach ($egresosCuentasRaw as $row) {
            $totalCuenta = number_format((float) $row->total, 2, '.', '');
            $porcentaje = ((float) $egresosOrdinarios > 0)
                ? round(((float) $totalCuenta / (float) $egresosOrdinarios) * 100, 2)
                : 0.0;

            $egresosPorCuenta[] = [
                'id' => $row->id,
                'codigo' => $row->codigo,
                'nombre' => $row->nombre,
                'total' => $totalCuenta,
                'porcentaje' => $porcentaje,
            ];
        }

        // 7. Matriz egresos cuenta × mes
        $matrizEgresosRaw = DB::table('egresos as e')
            ->join('catalogo_egresos as c', 'c.id', '=', 'e.cuenta_egreso_id')
            ->where('e.caja_id', $caja->id)
            ->whereNull('e.deleted_at')
            ->whereNull('e.transferencia_id')
            ->whereDate('e.fecha', '>=', $desdeStr)
            ->whereDate('e.fecha', '<=', $hastaStr)
            ->select('c.id', 'c.codigo', 'c.nombre', DB::raw("DATE_FORMAT(e.fecha, '%Y-%m') as mes"), DB::raw('SUM(e.monto) as total'))
            ->groupBy('c.id', 'c.codigo', 'c.nombre', DB::raw("DATE_FORMAT(e.fecha, '%Y-%m')"))
            ->orderBy('c.codigo', 'asc')
            ->get();

        // Agrupar matriz por cuenta
        $filasMatriz = [];
        foreach ($matrizEgresosRaw as $row) {
            $cuentaId = $row->id;
            if (! isset($filasMatriz[$cuentaId])) {
                $valoresIniciales = [];
                foreach ($meses as $mClave => $mInfo) {
                    $valoresIniciales[$mClave] = '0.00';
                }
                $filasMatriz[$cuentaId] = [
                    'id' => $row->id,
                    'codigo' => $row->codigo,
                    'nombre' => $row->nombre,
                    'valores' => $valoresIniciales,
                    'total' => '0.00',
                ];
            }
            $filasMatriz[$cuentaId]['valores'][$row->mes] = number_format((float) $row->total, 2, '.', '');
        }

        // Calcular totales de fila
        foreach ($filasMatriz as $cuentaId => &$fila) {
            $totalFila = '0.00';
            foreach ($fila['valores'] as $val) {
                $totalFila = bcadd($totalFila, $val, 2);
            }
            $fila['total'] = $totalFila;
        }
        unset($fila);

        // Calcular totales por columna (por mes)
        $totalesPorMesMatriz = [];
        $granTotalMatriz = '0.00';
        foreach ($meses as $mClave => $mInfo) {
            $sumaMes = '0.00';
            foreach ($filasMatriz as $fila) {
                $sumaMes = bcadd($sumaMes, $fila['valores'][$mClave], 2);
            }
            $totalesPorMesMatriz[$mClave] = $sumaMes;
            $granTotalMatriz = bcadd($granTotalMatriz, $sumaMes, 2);
        }

        $matrizEgresos = [
            'meses' => array_values($meses),
            'filas' => array_values($filasMatriz),
            'totales_mes' => $totalesPorMesMatriz,
            'total_general' => $granTotalMatriz,
            'cruza_meses' => count($meses) > 1,
        ];

        // 8. Transferencias detalladas (RN-11)
        $transferenciasRecibidasList = $caja->ingresos()
            ->whereNotNull('transferencia_id')
            ->whereDate('fecha', '>=', $desdeStr)
            ->whereDate('fecha', '<=', $hastaStr)
            ->with(['transferencia.cajaOrigen'])
            ->orderBy('fecha')
            ->get()
            ->map(function ($ingreso) {
                return [
                    'fecha' => $ingreso->fecha->format('d/m/Y'),
                    'caja_origen' => $ingreso->transferencia?->cajaOrigen?->nombre ?? 'Caja externa',
                    'caja_origen_codigo' => $ingreso->transferencia?->cajaOrigen?->codigo ?? '-',
                    'concepto' => $ingreso->transferencia?->concepto ?? $ingreso->observaciones ?? 'Transferencia recibida',
                    'monto' => number_format((float) $ingreso->monto, 2, '.', ''),
                ];
            })
            ->all();

        $transferenciasEnviadasList = $caja->egresos()
            ->whereNotNull('transferencia_id')
            ->whereDate('fecha', '>=', $desdeStr)
            ->whereDate('fecha', '<=', $hastaStr)
            ->with(['transferencia.cajaDestino'])
            ->orderBy('fecha')
            ->get()
            ->map(function ($egreso) {
                return [
                    'fecha' => $egreso->fecha->format('d/m/Y'),
                    'caja_destino' => $egreso->transferencia?->cajaDestino?->nombre ?? 'Caja externa',
                    'caja_destino_codigo' => $egreso->transferencia?->cajaDestino?->codigo ?? '-',
                    'concepto' => $egreso->transferencia?->concepto ?? $egreso->observaciones ?? 'Transferencia enviada',
                    'monto' => number_format((float) $egreso->monto, 2, '.', ''),
                ];
            })
            ->all();

        $transferencias = [
            'recibidas' => $transferenciasRecibidasList,
            'total_recibidas' => $transferenciasRecibidas,
            'enviadas' => $transferenciasEnviadasList,
            'total_enviadas' => $transferenciasEnviadas,
        ];

        // 9. Firmas institucionales
        $firmas = [
            ['cargo' => 'Pastor / Presidente', 'nombre' => ''],
            ['cargo' => 'Tesorero(a) Titular', 'nombre' => ''],
            ['cargo' => 'Vocal / Auditor Interno', 'nombre' => ''],
        ];

        return [
            'cabecera' => $cabecera,
            'resumen' => $resumen,
            'ingresos_por_cuenta' => $ingresosPorCuenta,
            'total_ingresos_cuentas' => $ingresosOrdinarios,
            'ingresos_por_mes' => array_values($ingresosPorMes),
            'egresos_por_cuenta' => $egresosPorCuenta,
            'total_egresos_cuentas' => $egresosOrdinarios,
            'matriz_egresos' => $matrizEgresos,
            'transferencias' => $transferencias,
            'firmas' => $firmas,
        ];
    }
}
