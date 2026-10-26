<?php

namespace App\Services;

use App\Enums\EstadoCorte;
use App\Models\Caja;
use App\Models\CorteCaja;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Transferencia;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Service InformeGeneralService
 *
 * Fuente ÚNICA de consolidación contable institucional para la Asamblea General.
 * Implementa:
 * - D3-10: Cero tablas recalculadas en base de datos; cálculo atómico en memoria.
 * - RN3-03: Exclusividad de cortes_caja en estado "aprobado". Departamentos sin cortes
 *           aprobados en el período se presentan explícitamente como "Sin información",
 *           nunca con ceros silenciosos ni estimaciones.
 * - RN3-04: Exclusión de transferencias internas de los totales de ingresos y egresos
 *           externos de la iglesia, presentándolas en sección separada de conciliación.
 * - Alerta de fondos no formalizados: Detección de cajas con movimientos sin cortes aprobados.
 */
class InformeGeneralService
{
    /**
     * Genera la estructura completa del Informe General de Asamblea.
     *
     * @return array<string, mixed>
     */
    public function generar(CarbonInterface|string $desde, CarbonInterface|string $hasta): array
    {
        $fechaDesde = is_string($desde) ? Carbon::parse($desde) : $desde->copy();
        $fechaHasta = is_string($hasta) ? Carbon::parse($hasta) : $hasta->copy();

        $desdeStr = $fechaDesde->format('Y-m-d');
        $hastaStr = $fechaHasta->format('Y-m-d');

        $cabecera = [
            'entidad' => 'Iglesia Evangélica Nacional Presbiteriana de Guatemala "Getsemaní"',
            'titulo' => 'Informe General Consolidado de Asamblea',
            'periodo_desde' => $desdeStr,
            'periodo_hasta' => $hastaStr,
            'periodo_texto' => 'Del '.$fechaDesde->format('d/m/Y').' al '.$fechaHasta->format('d/m/Y'),
            'moneda' => 'Quetzales (Q)',
            'generado_el' => now()->format('d/m/Y H:i'),
        ];

        $departamentos = Departamento::with(['cajas' => function ($q) {
            $q->orderBy('nombre');
        }])->orderBy('nombre')->get();

        $filasDepartamentos = [];
        $advertencias = [];

        $globalSaldoInicial = '0.00';
        $globalIngresosOrdinarios = '0.00';
        $globalEgresosOrdinarios = '0.00';
        $globalTransferenciasRecibidas = '0.00';
        $globalTransferenciasEnviadas = '0.00';
        $globalTotalIngresos = '0.00';
        $globalTotalEgresos = '0.00';
        $globalSaldoFinal = '0.00';
        $deptosConInfo = 0;

        foreach ($departamentos as $depto) {
            $cajasIds = $depto->cajas->pluck('id')->all();

            if (empty($cajasIds)) {
                $filasDepartamentos[] = [
                    'departamento_id' => $depto->id,
                    'departamento' => $depto->nombre,
                    'tiene_informacion' => false,
                    'estado' => 'sin_cajas',
                    'mensaje' => 'Sin cajas registradas',
                    'saldo_inicial' => null,
                    'ingresos_ordinarios' => null,
                    'egresos_ordinarios' => null,
                    'transferencias_recibidas' => null,
                    'transferencias_enviadas' => null,
                    'total_ingresos' => null,
                    'total_egresos' => null,
                    'saldo_final' => null,
                    'neto' => null,
                    'cajas_con_cortes' => 0,
                    'total_cortes' => 0,
                ];

                continue;
            }

            // Consultar cortes aprobados del departamento en el rango
            $cortesAprobados = CorteCaja::whereIn('caja_id', $cajasIds)
                ->where('estado', EstadoCorte::Aprobado)
                ->whereDate('periodo_inicio', '>=', $desdeStr)
                ->whereDate('periodo_fin', '<=', $hastaStr)
                ->orderBy('periodo_inicio')
                ->get();

            // Advertencia: verificar si hay movimientos registrados sin corte aprobado
            $tieneMovimientos = Ingreso::whereIn('caja_id', $cajasIds)
                ->whereDate('fecha', '>=', $desdeStr)
                ->whereDate('fecha', '<=', $hastaStr)
                ->exists() || Egreso::whereIn('caja_id', $cajasIds)
                ->whereDate('fecha', '>=', $desdeStr)
                ->whereDate('fecha', '<=', $hastaStr)
                ->exists();

            if ($cortesAprobados->isEmpty()) {
                if ($tieneMovimientos) {
                    $advertencias[] = [
                        'departamento_id' => $depto->id,
                        'departamento' => $depto->nombre,
                        'tipo' => 'sin_cortes_con_movimientos',
                        'mensaje' => "El departamento '{$depto->nombre}' posee movimientos registrados en el período pero ningún corte de caja aprobado. Estos fondos no están formalizados ante la Asamblea.",
                    ];
                }

                // RN3-03: No ceros silenciosos, marcar explícitamente "Sin información del período"
                $filasDepartamentos[] = [
                    'departamento_id' => $depto->id,
                    'departamento' => $depto->nombre,
                    'tiene_informacion' => false,
                    'estado' => 'sin_informacion',
                    'mensaje' => 'Sin información del período',
                    'saldo_inicial' => null,
                    'ingresos_ordinarios' => null,
                    'egresos_ordinarios' => null,
                    'transferencias_recibidas' => null,
                    'transferencias_enviadas' => null,
                    'total_ingresos' => null,
                    'total_egresos' => null,
                    'saldo_final' => null,
                    'neto' => null,
                    'cajas_con_cortes' => 0,
                    'total_cortes' => 0,
                ];

                continue;
            }

            // Agrupar por caja para determinar saldos iniciales y finales reales
            $deptoSaldoInicial = '0.00';
            $deptoSaldoFinal = '0.00';
            $deptoTotalIngresos = '0.00';
            $deptoTotalEgresos = '0.00';
            $cajasProcesadas = [];

            $cortesPorCaja = $cortesAprobados->groupBy('caja_id');

            foreach ($cortesPorCaja as $cajaId => $cortesCaja) {
                $cajasProcesadas[] = $cajaId;
                $primerCorte = $cortesCaja->first();
                $ultimoCorte = $cortesCaja->last();

                $deptoSaldoInicial = bcadd($deptoSaldoInicial, (string) $primerCorte->saldo_inicial, 2);
                $deptoSaldoFinal = bcadd($deptoSaldoFinal, (string) $ultimoCorte->saldo_final, 2);

                foreach ($cortesCaja as $c) {
                    $deptoTotalIngresos = bcadd($deptoTotalIngresos, (string) $c->total_ingresos, 2);
                    $deptoTotalEgresos = bcadd($deptoTotalEgresos, (string) $c->total_egresos, 2);
                }
            }

            // RN3-04: Cuantificar transferencias internas en el periodo de los cortes aprobados
            $primerInicio = $cortesAprobados->min('periodo_inicio')?->format('Y-m-d') ?? $desdeStr;
            $ultimoFin = $cortesAprobados->max('periodo_fin')?->format('Y-m-d') ?? $hastaStr;

            $deptoTransferenciasRecibidas = number_format((float) Ingreso::whereIn('caja_id', $cajasProcesadas)
                ->whereNotNull('transferencia_id')
                ->whereDate('fecha', '>=', $primerInicio)
                ->whereDate('fecha', '<=', $ultimoFin)
                ->sum('monto'), 2, '.', '');

            $deptoTransferenciasEnviadas = number_format((float) Egreso::whereIn('caja_id', $cajasProcesadas)
                ->whereNotNull('transferencia_id')
                ->whereDate('fecha', '>=', $primerInicio)
                ->whereDate('fecha', '<=', $ultimoFin)
                ->sum('monto'), 2, '.', '');

            // Ingresos y egresos externos (ordinarios)
            $deptoIngresosOrdinarios = bcsub($deptoTotalIngresos, $deptoTransferenciasRecibidas, 2);
            $deptoEgresosOrdinarios = bcsub($deptoTotalEgresos, $deptoTransferenciasEnviadas, 2);
            $deptoNeto = bcsub($deptoTotalIngresos, $deptoTotalEgresos, 2);

            $filasDepartamentos[] = [
                'departamento_id' => $depto->id,
                'departamento' => $depto->nombre,
                'tiene_informacion' => true,
                'estado' => 'aprobado',
                'mensaje' => 'Información oficial verificada',
                'saldo_inicial' => $deptoSaldoInicial,
                'ingresos_ordinarios' => $deptoIngresosOrdinarios,
                'egresos_ordinarios' => $deptoEgresosOrdinarios,
                'transferencias_recibidas' => $deptoTransferenciasRecibidas,
                'transferencias_enviadas' => $deptoTransferenciasEnviadas,
                'total_ingresos' => $deptoTotalIngresos,
                'total_egresos' => $deptoTotalEgresos,
                'saldo_final' => $deptoSaldoFinal,
                'neto' => $deptoNeto,
                'cajas_con_cortes' => count($cajasProcesadas),
                'total_cortes' => $cortesAprobados->count(),
            ];

            // Acumular a totales globales
            $globalSaldoInicial = bcadd($globalSaldoInicial, $deptoSaldoInicial, 2);
            $globalIngresosOrdinarios = bcadd($globalIngresosOrdinarios, $deptoIngresosOrdinarios, 2);
            $globalEgresosOrdinarios = bcadd($globalEgresosOrdinarios, $deptoEgresosOrdinarios, 2);
            $globalTransferenciasRecibidas = bcadd($globalTransferenciasRecibidas, $deptoTransferenciasRecibidas, 2);
            $globalTransferenciasEnviadas = bcadd($globalTransferenciasEnviadas, $deptoTransferenciasEnviadas, 2);
            $globalTotalIngresos = bcadd($globalTotalIngresos, $deptoTotalIngresos, 2);
            $globalTotalEgresos = bcadd($globalTotalEgresos, $deptoTotalEgresos, 2);
            $globalSaldoFinal = bcadd($globalSaldoFinal, $deptoSaldoFinal, 2);
            $deptosConInfo++;
        }

        // RN3-04: Listado de transferencias internas entre cajas en el período
        $transferencias = Transferencia::with(['cajaOrigen.departamento', 'cajaDestino.departamento', 'usuario'])
            ->whereDate('fecha', '>=', $desdeStr)
            ->whereDate('fecha', '<=', $hastaStr)
            ->orderBy('fecha')
            ->get();

        $totalTransferenciasPeriodo = number_format((float) $transferencias->sum('monto'), 2, '.', '');

        $totalesGlobales = [
            'saldo_inicial' => $globalSaldoInicial,
            'ingresos_ordinarios' => $globalIngresosOrdinarios,
            'egresos_ordinarios' => $globalEgresosOrdinarios,
            'neto_ordinario' => bcsub($globalIngresosOrdinarios, $globalEgresosOrdinarios, 2),
            'transferencias_recibidas' => $globalTransferenciasRecibidas,
            'transferencias_enviadas' => $globalTransferenciasEnviadas,
            'total_ingresos' => $globalTotalIngresos,
            'total_egresos' => $globalTotalEgresos,
            'saldo_final' => $globalSaldoFinal,
            'departamentos_con_info' => $deptosConInfo,
            'departamentos_total' => $departamentos->count(),
        ];

        return [
            'cabecera' => $cabecera,
            'departamentos' => $filasDepartamentos,
            'totales' => $totalesGlobales,
            'transferencias' => [
                'total_monto' => $totalTransferenciasPeriodo,
                'items' => $transferencias,
            ],
            'advertencias' => $advertencias,
        ];
    }
}
