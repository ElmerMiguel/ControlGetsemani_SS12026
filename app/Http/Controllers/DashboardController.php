<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCorte;
use App\Models\Caja;
use App\Models\CorteCaja;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Services\CajaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Muestra la pantalla principal del panel de control con métricas en tiempo real.
     */
    public function index(Request $request, CajaService $cajaService): View
    {
        $user = $request->user();
        $esAdmin = $user->hasRole('admin');

        $anioActual = now()->year;
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        if ($esAdmin) {
            // ==================== DASHBOARD ADMINISTRADOR ====================
            $cajas = Caja::with('departamento')->orderBy('codigo')->get();

            $saldoTotal = '0.00';
            $cajasNegativas = [];
            $saldosPorDepto = [];

            foreach ($cajas as $caja) {
                $saldo = $cajaService->saldoActual($caja);
                $caja->saldo_calculado = $saldo;
                $saldoTotal = bcadd($saldoTotal, $saldo, 2);

                if (bccomp($saldo, '0.00', 2) < 0) {
                    $cajasNegativas[] = [
                        'caja' => $caja,
                        'saldo' => $saldo,
                    ];
                }

                $deptoNombre = $caja->departamento->nombre ?? 'Sin Departamento';
                if (! isset($saldosPorDepto[$deptoNombre])) {
                    $saldosPorDepto[$deptoNombre] = [
                        'nombre' => $deptoNombre,
                        'saldo' => '0.00',
                        'cajas_count' => 0,
                    ];
                }
                $saldosPorDepto[$deptoNombre]['saldo'] = bcadd($saldosPorDepto[$deptoNombre]['saldo'], $saldo, 2);
                $saldosPorDepto[$deptoNombre]['cajas_count']++;
            }

            // Gráfica mensual global de toda la iglesia
            $serieGlobal = $cajaService->serieMensual(null, $anioActual, $user);
            $chartData = [
                'labels' => $serieGlobal['labels'],
                'datasets' => [
                    [
                        'label' => 'Ingresos',
                        'data' => $serieGlobal['ingresos'],
                        'backgroundColor' => '#22c55e',
                        'borderRadius' => 4,
                    ],
                    [
                        'label' => 'Egresos',
                        'data' => $serieGlobal['egresos'],
                        'backgroundColor' => '#ef4444',
                        'borderRadius' => 4,
                    ],
                ],
            ];

            // Cortes pendientes de revisión
            $cortesPendientesCount = CorteCaja::where('estado', EstadoCorte::Pendiente)->count();

            // Totales externos del mes
            $totalesMes = $cajaService->totalesMesActual(null, $user);

            // Últimos 10 movimientos globales
            $ultimosIngresos = Ingreso::with(['caja', 'cuenta', 'aportante'])
                ->latest('fecha')
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn ($m) => tap($m, fn ($i) => $i->tipo_movimiento = 'ingreso'));

            $ultimosEgresos = Egreso::with(['caja', 'cuenta'])
                ->latest('fecha')
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn ($m) => tap($m, fn ($e) => $e->tipo_movimiento = 'egreso'));

            $ultimosMovimientos = $ultimosIngresos->concat($ultimosEgresos)
                ->sort(function ($a, $b) {
                    if ($a->fecha->equalTo($b->fecha)) {
                        return $b->id <=> $a->id;
                    }

                    return $b->fecha <=> $a->fecha;
                })
                ->take(10)
                ->values();

            return view('dashboard', compact(
                'user',
                'esAdmin',
                'saldoTotal',
                'totalesMes',
                'cortesPendientesCount',
                'cajasNegativas',
                'saldosPorDepto',
                'chartData',
                'ultimosMovimientos',
                'anioActual'
            ));
        }

        // ==================== DASHBOARD TESORERO ====================
        $cajasAsignadas = Caja::accesiblesPara($user)->with('departamento')->orderBy('codigo')->get();

        foreach ($cajasAsignadas as $caja) {
            $caja->saldo_calculado = $cajaService->saldoActual($caja);
        }

        // Determinar caja activa (sesión o primera caja accesible)
        $cajaActivaId = session('caja_activa_id');
        $cajaActiva = $cajasAsignadas->firstWhere('id', $cajaActivaId) ?? $cajasAsignadas->first();

        // Saldo y métricas de la caja activa
        $saldoCajaActiva = $cajaActiva ? $cajaService->saldoActual($cajaActiva) : '0.00';
        $totalesMes = $cajaService->totalesMesActual($cajaActiva, $user);

        // Gráfica mensual de la caja activa
        $serie = $cajaService->serieMensual($cajaActiva, $anioActual, $user);
        $chartData = [
            'labels' => $serie['labels'],
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => $serie['ingresos'],
                    'backgroundColor' => '#22c55e',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Egresos',
                    'data' => $serie['egresos'],
                    'backgroundColor' => '#ef4444',
                    'borderRadius' => 4,
                ],
            ],
        ];

        // Top 5 egresos del mes
        $topEgresos = $cajaService->topEgresos($cajaActiva, $inicioMes, $finMes, 5, $user);

        // Últimos 10 movimientos
        $queryIngresos = Ingreso::accesiblesPara($user)
            ->with(['caja', 'cuenta', 'aportante'])
            ->latest('fecha')
            ->latest('id')
            ->limit(10);

        $queryEgresos = Egreso::accesiblesPara($user)
            ->with(['caja', 'cuenta'])
            ->latest('fecha')
            ->latest('id')
            ->limit(10);

        if ($cajaActiva) {
            $queryIngresos->where('caja_id', $cajaActiva->id);
            $queryEgresos->where('caja_id', $cajaActiva->id);
        }

        $ingresos = $queryIngresos->get()->map(fn ($m) => tap($m, fn ($i) => $i->tipo_movimiento = 'ingreso'));
        $egresos = $queryEgresos->get()->map(fn ($m) => tap($m, fn ($e) => $e->tipo_movimiento = 'egreso'));

        $ultimosMovimientos = $ingresos->concat($egresos)
            ->sort(function ($a, $b) {
                if ($a->fecha->equalTo($b->fecha)) {
                    return $b->id <=> $a->id;
                }

                return $b->fecha <=> $a->fecha;
            })
            ->take(10)
            ->values();

        return view('dashboard', compact(
            'user',
            'esAdmin',
            'cajasAsignadas',
            'cajaActiva',
            'saldoCajaActiva',
            'totalesMes',
            'chartData',
            'topEgresos',
            'ultimosMovimientos',
            'anioActual'
        ));
    }
}
