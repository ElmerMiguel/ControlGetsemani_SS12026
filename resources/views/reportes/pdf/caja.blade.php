<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Caja - {{ $cabecera['caja'] }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 10mm 15mm 10mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #1e293b;
            line-height: 1.25;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 13pt;
            margin: 0;
            color: #0f172a;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 10pt;
            margin: 2px 0;
            color: #0284c7;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px 4px;
            font-size: 8pt;
        }
        .meta-label {
            font-weight: bold;
            color: #475569;
            width: 15%;
        }
        .meta-val {
            color: #0f172a;
            width: 35%;
        }
        
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-top: 10px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            font-size: 7.5pt;
            padding: 4px 6px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        table.data-table th.text-right,
        table.data-table td.text-right {
            text-align: right;
        }
        table.data-table th.text-center,
        table.data-table td.text-center {
            text-align: center;
        }
        table.data-table td {
            font-size: 7.5pt;
            padding: 3px 5px;
            border: 1px solid #cbd5e1;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        table.data-table tr.total-row td {
            font-weight: bold;
            background-color: #f1f5f9;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
        }

        .summary-box {
            width: 100%;
            margin-bottom: 12px;
        }
        .summary-cell {
            padding: 6px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            text-align: center;
        }
        .summary-label {
            font-size: 7pt;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            display: block;
        }
        .summary-amount {
            font-size: 10pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .summary-amount.green { color: #059669; }
        .summary-amount.red { color: #dc2626; }
        .summary-amount.blue { color: #0284c7; }

        .two-col {
            width: 100%;
            border-collapse: collapse;
        }
        .two-col td {
            vertical-align: top;
            width: 50%;
            padding: 0 6px;
        }

        .page-break {
            page-break-before: always;
        }

        .signatures-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }
        .sign-line {
            border-top: 1px solid #475569;
            margin-bottom: 4px;
            padding-top: 2px;
        }
        .sign-title {
            font-size: 8pt;
            font-weight: bold;
            color: #1e293b;
        }
        .sign-subtitle {
            font-size: 7pt;
            color: #64748b;
        }

        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            height: 8mm;
            text-align: center;
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 2px;
        }
    </style>
</head>
<body>

    <div class="footer">
        {{ $cabecera['entidad'] }} &bull; Reporte Financiero de Caja &bull; Cifras expresadas en Quetzales (Q)
    </div>

    <!-- Encabezado Institucional -->
    <div class="header">
        <h1>{{ $cabecera['entidad'] }}</h1>
        <h2>INFORME FINANCIERO DE CAJA</h2>
        <div style="font-size: 7.5pt; color: #64748b; margin-top: 2px;">
            Generado el: {{ $cabecera['generado_el'] }} &bull; Moneda: {{ $cabecera['moneda'] }}
        </div>
    </div>

    <!-- Metadatos de la Caja y Período -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Departamento:</td>
            <td class="meta-val">{{ $cabecera['departamento'] }}</td>
            <td class="meta-label">Período:</td>
            <td class="meta-val">{{ $cabecera['periodo_texto'] }}</td>
        </tr>
        <tr>
            <td class="meta-label">Caja:</td>
            <td class="meta-val">{{ $cabecera['caja'] }} ({{ $cabecera['caja_codigo'] }})</td>
            <td class="meta-label">Cifras:</td>
            <td class="meta-val">Expresadas en Quetzales (Q)</td>
        </tr>
    </table>

    <!-- Tarjetas de Resumen General -->
    <div class="section-title">Resumen Financiero del Período</div>
    <table style="width: 100%; border-collapse: separate; border-spacing: 4px; margin-bottom: 12px;">
        <tr>
            <td class="summary-cell" style="width: 20%;">
                <span class="summary-label">Saldo Inicial</span>
                <span class="summary-amount">{{ formato_moneda($resumen['saldo_inicial']) }}</span>
            </td>
            <td class="summary-cell" style="width: 20%;">
                <span class="summary-label">(+) Total Ingresos</span>
                <span class="summary-amount green">{{ formato_moneda($resumen['total_ingresos']) }}</span>
            </td>
            <td class="summary-cell" style="width: 20%;">
                <span class="summary-label">(-) Total Egresos</span>
                <span class="summary-amount red">{{ formato_moneda($resumen['total_egresos']) }}</span>
            </td>
            <td class="summary-cell" style="width: 20%;">
                <span class="summary-label">(=) Flujo Neto</span>
                <span class="summary-amount {{ (float) $resumen['neto_periodo'] >= 0 ? 'green' : 'red' }}">
                    {{ formato_moneda($resumen['neto_periodo']) }}
                </span>
            </td>
            <td class="summary-cell" style="width: 20%; background-color: #f0f9ff; border: 1.5px solid #0284c7;">
                <span class="summary-label" style="color: #0369a1;">(=) Saldo Final</span>
                <span class="summary-amount blue">{{ formato_moneda($resumen['saldo_final']) }}</span>
            </td>
        </tr>
    </table>

    <!-- Desglose de Cuentas: Ingresos y Egresos -->
    <table class="two-col">
        <tr>
            <!-- Ingresos por Cuenta -->
            <td>
                <div class="section-title">Ingresos Ordinarios por Cuenta</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Código</th>
                            <th>Cuenta de Ingreso</th>
                            <th class="text-right" style="width: 25%;">Total (Q)</th>
                            <th class="text-right" style="width: 15%;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ingresos_por_cuenta as $item)
                            <tr>
                                <td class="text-center font-mono">{{ $item['codigo'] }}</td>
                                <td>{{ $item['nombre'] }}</td>
                                <td class="text-right font-mono">{{ formato_moneda($item['total']) }}</td>
                                <td class="text-right font-mono">{{ number_format($item['porcentaje'], 2) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="color: #94a3b8; font-style: italic;">
                                    Sin ingresos ordinarios registrados en el período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="2">TOTAL INGRESOS ORDINARIOS</td>
                            <td class="text-right font-mono">{{ formato_moneda($total_ingresos_cuentas) }}</td>
                            <td class="text-right font-mono">100.00%</td>
                        </tr>
                    </tfoot>
                </table>
            </td>

            <!-- Egresos por Cuenta -->
            <td>
                <div class="section-title">Egresos Ordinarios por Cuenta</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Código</th>
                            <th>Cuenta de Egreso</th>
                            <th class="text-right" style="width: 25%;">Total (Q)</th>
                            <th class="text-right" style="width: 15%;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($egresos_por_cuenta as $item)
                            <tr>
                                <td class="text-center font-mono">{{ $item['codigo'] }}</td>
                                <td>{{ $item['nombre'] }}</td>
                                <td class="text-right font-mono">{{ formato_moneda($item['total']) }}</td>
                                <td class="text-right font-mono">{{ number_format($item['porcentaje'], 2) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="color: #94a3b8; font-style: italic;">
                                    Sin egresos ordinarios registrados en el período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="2">TOTAL EGRESOS ORDINARIOS</td>
                            <td class="text-right font-mono">{{ formato_moneda($total_egresos_cuentas) }}</td>
                            <td class="text-right font-mono">100.00%</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
    </table>

    <!-- Transferencias internas si existen (RN-11) -->
    @if (! empty($transferencias['recibidas']) || ! empty($transferencias['enviadas']))
        <table class="two-col" style="margin-top: 10px;">
            <tr>
                <td>
                    <div class="section-title">Transferencias Recibidas (Internas)</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Fecha</th>
                                <th>Caja Origen</th>
                                <th>Concepto</th>
                                <th class="text-right" style="width: 25%;">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transferencias['recibidas'] as $tr)
                                <tr>
                                    <td class="text-center font-mono">{{ $tr['fecha'] }}</td>
                                    <td>{{ $tr['caja_origen'] }}</td>
                                    <td>{{ $tr['concepto'] }}</td>
                                    <td class="text-right font-mono">{{ formato_moneda($tr['monto']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center" style="color: #94a3b8; font-style: italic;">
                                        Sin transferencias recibidas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3">TOTAL RECIBIDAS</td>
                                <td class="text-right font-mono">{{ formato_moneda($transferencias['total_recibidas']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
                <td>
                    <div class="section-title">Transferencias Enviadas (Internas)</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Fecha</th>
                                <th>Caja Destino</th>
                                <th>Concepto</th>
                                <th class="text-right" style="width: 25%;">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transferencias['enviadas'] as $te)
                                <tr>
                                    <td class="text-center font-mono">{{ $te['fecha'] }}</td>
                                    <td>{{ $te['caja_destino'] }}</td>
                                    <td>{{ $te['concepto'] }}</td>
                                    <td class="text-right font-mono">{{ formato_moneda($te['monto']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center" style="color: #94a3b8; font-style: italic;">
                                        Sin transferencias enviadas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3">TOTAL ENVIADAS</td>
                                <td class="text-right font-mono">{{ formato_moneda($transferencias['total_enviadas']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    <!-- Matriz de Egresos Cuenta × Mes -->
    <div style="page-break-inside: avoid; margin-top: 10px;">
        <div class="section-title">Matriz de Egresos Ordinarios (Cuenta &times; Mes)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 7%;">Código</th>
                    <th>Cuenta de Egreso</th>
                    @foreach ($matriz_egresos['meses'] as $m)
                        <th class="text-right">{{ $m['nombre'] }}</th>
                    @endforeach
                    <th class="text-right" style="width: 12%;">Total Cuenta</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($matriz_egresos['filas'] as $fila)
                    <tr>
                        <td class="text-center font-mono">{{ $fila['codigo'] }}</td>
                        <td>{{ $fila['nombre'] }}</td>
                        @foreach ($matriz_egresos['meses'] as $m)
                            <td class="text-right font-mono">
                                {{ (float) $fila['valores'][$m['clave']] > 0 ? formato_moneda($fila['valores'][$m['clave']]) : '-' }}
                            </td>
                        @endforeach
                        <td class="text-right font-mono" style="font-weight: bold;">
                            {{ formato_moneda($fila['total']) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + count($matriz_egresos['meses']) }}" class="text-center" style="color: #94a3b8; font-style: italic;">
                            No se registran egresos en el período analizado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2">TOTAL MENSUAL DE EGRESOS</td>
                    @foreach ($matriz_egresos['meses'] as $m)
                        <td class="text-right font-mono">
                            {{ formato_moneda($matriz_egresos['totales_mes'][$m['clave']] ?? '0.00') }}
                        </td>
                    @endforeach
                    <td class="text-right font-mono" style="font-weight: bold; background-color: #e2e8f0;">
                        {{ formato_moneda($matriz_egresos['total_general']) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Bloque de Firmas Institucionales -->
    <div style="page-break-inside: avoid; margin-top: 28px;">
        <table class="signatures-table">
            <tr>
                @foreach ($firmas as $firma)
                    <td>
                        <div style="height: 35px;"></div>
                        <div class="sign-line"></div>
                        <div class="sign-title">{{ $firma['cargo'] }}</div>
                        <div class="sign-subtitle">{{ $cabecera['caja'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

</body>
</html>
