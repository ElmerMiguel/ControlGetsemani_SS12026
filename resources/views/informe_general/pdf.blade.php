<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe General de Asamblea</title>
    <style>
        @page {
            size: letter landscape;
            margin: 10mm 12mm 10mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            color: #111;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 1.5px solid #111;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 11pt;
            margin: 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header h2 {
            font-size: 10pt;
            margin: 2px 0 0 0;
            font-weight: bold;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 7.5pt;
            color: #333;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .meta-table td {
            font-size: 7.5pt;
            padding: 2px 4px;
        }
        .section-title {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #111;
            padding-bottom: 2px;
            margin-top: 10px;
            margin-bottom: 6px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table th {
            border-top: 1.5px solid #111;
            border-bottom: 1.5px solid #111;
            background-color: #f3f3f3;
            padding: 4px 5px;
            font-size: 7pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #111;
        }
        table.data-table td {
            border-bottom: 1px dotted #ccc;
            padding: 4px 5px;
            font-size: 7.5pt;
            vertical-align: middle;
        }
        table.data-table tfoot td {
            border-top: 1.5px solid #111;
            border-bottom: 2px solid #111;
            background-color: #f3f3f3;
            font-weight: bold;
            font-size: 8pt;
            padding: 5px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: monospace;
        }
        .firmas-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .firmas-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 15px;
        }
        .linea-firma {
            border-top: 1px solid #111;
            margin-bottom: 4px;
        }
        .firma-nombre {
            font-weight: bold;
            font-size: 7.5pt;
        }
        .firma-cargo {
            font-size: 6.5pt;
            color: #444;
            text-transform: uppercase;
        }
        .footer {
            margin-top: 15px;
            border-top: 1px solid #ccc;
            padding-top: 4px;
            text-align: center;
            font-size: 6.5pt;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Iglesia Evangélica Nacional Presbiteriana de Guatemala</h1>
        <h2>TEMPLO "GETSEMANÍ" &bull; INFORME GENERAL DE ASAMBLEA</h2>
        <p>Sistema Institucional de Tesorería, Balances y Control Contable</p>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <strong>Período Oficial:</strong> {{ $cabecera['periodo_texto'] }}
            </td>
            <td style="width: 50%; text-align: right;">
                <strong>Fecha de Emisión:</strong> {{ $cabecera['generado_el'] }} &bull; <strong>Moneda:</strong> {{ $cabecera['moneda'] }}
            </td>
        </tr>
    </table>

    <div class="section-title">1. Resumen Consolidado por Departamento (Cortes de Caja Aprobados)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 20%;">Departamento</th>
                <th style="width: 10%; text-align: center;">Estado</th>
                <th class="text-right" style="width: 9%;">Saldo Inicial</th>
                <th class="text-right" style="width: 9%;">(+) Ing. Ordinarios</th>
                <th class="text-right" style="width: 8%;">(+) Transf. Rec.</th>
                <th class="text-right" style="width: 9%;">Total Ingresos</th>
                <th class="text-right" style="width: 9%;">(-) Egr. Ordinarios</th>
                <th class="text-right" style="width: 8%;">(-) Transf. Env.</th>
                <th class="text-right" style="width: 9%;">Total Egresos</th>
                <th class="text-right" style="width: 9%;">Saldo Final</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($departamentos as $depto)
                <tr>
                    <td><strong>{{ $depto['departamento'] }}</strong></td>
                    <td class="text-center">
                        @if ($depto['tiene_informacion'])
                            Oficial ({{ $depto['total_cortes'] }})
                        @else
                            {{ $depto['mensaje'] }}
                        @endif
                    </td>
                    <td class="text-right font-mono">{{ $depto['tiene_informacion'] ? formato_moneda($depto['saldo_inicial']) : '—' }}</td>
                    <td class="text-right font-mono">{{ $depto['tiene_informacion'] ? formato_moneda($depto['ingresos_ordinarios']) : '—' }}</td>
                    <td class="text-right font-mono">{{ $depto['tiene_informacion'] ? formato_moneda($depto['transferencias_recibidas']) : '—' }}</td>
                    <td class="text-right font-mono"><strong>{{ $depto['tiene_informacion'] ? formato_moneda($depto['total_ingresos']) : '—' }}</strong></td>
                    <td class="text-right font-mono">{{ $depto['tiene_informacion'] ? formato_moneda($depto['egresos_ordinarios']) : '—' }}</td>
                    <td class="text-right font-mono">{{ $depto['tiene_informacion'] ? formato_moneda($depto['transferencias_enviadas']) : '—' }}</td>
                    <td class="text-right font-mono"><strong>{{ $depto['tiene_informacion'] ? formato_moneda($depto['total_egresos']) : '—' }}</strong></td>
                    <td class="text-right font-mono"><strong>{{ $depto['tiene_informacion'] ? formato_moneda($depto['saldo_final']) : '—' }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL CONSOLIDADO ASAMBLEA</td>
                <td class="text-right font-mono">{{ formato_moneda($totales['saldo_inicial']) }}</td>
                <td class="text-right font-mono">{{ formato_moneda($totales['ingresos_ordinarios']) }}</td>
                <td class="text-right font-mono">{{ formato_moneda($totales['transferencias_recibidas']) }}</td>
                <td class="text-right font-mono">{{ formato_moneda($totales['total_ingresos']) }}</td>
                <td class="text-right font-mono">{{ formato_moneda($totales['egresos_ordinarios']) }}</td>
                <td class="text-right font-mono">{{ formato_moneda($totales['transferencias_enviadas']) }}</td>
                <td class="text-right font-mono">{{ formato_moneda($totales['total_egresos']) }}</td>
                <td class="text-right font-mono">{{ formato_moneda($totales['saldo_final']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">2. Movimientos y Transferencias Internas entre Cajas (RN-11 / RN3-04)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Fecha</th>
                <th style="width: 23%;">Caja y Depto. Origen</th>
                <th style="width: 23%;">Caja y Depto. Destino</th>
                <th style="width: 27%;">Concepto</th>
                <th class="text-right" style="width: 15%;">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transferencias['items'] as $t)
                <tr>
                    <td class="font-mono">{{ $t->fecha?->format('d/m/Y') }}</td>
                    <td>{{ $t->cajaOrigen?->nombre }} ({{ $t->cajaOrigen?->departamento?->nombre }})</td>
                    <td>{{ $t->cajaDestino?->nombre }} ({{ $t->cajaDestino?->departamento?->nombre }})</td>
                    <td>{{ $t->concepto }}</td>
                    <td class="text-right font-mono"><strong>{{ formato_moneda($t->monto) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="color: #666; padding: 6px;">
                        No se registraron movimientos internos entre cajas durante este período.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($transferencias['items']->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="4">TOTAL MOVIMIENTOS INTERNOS</td>
                    <td class="text-right font-mono">{{ formato_moneda($transferencias['total_monto']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <table class="firmas-table">
        <tr>
            <td>
                <div class="linea-firma"></div>
                <div class="firma-nombre">Administrador General</div>
                <div class="firma-cargo">Pastor Presidente / Moderador</div>
            </td>
            <td>
                <div class="linea-firma"></div>
                <div class="firma-nombre">Tesorero General</div>
                <div class="firma-cargo">Consistorio / Presbiterio</div>
            </td>
            <td>
                <div class="linea-firma"></div>
                <div class="firma-nombre">Comisión Revisora de Cuentas</div>
                <div class="firma-cargo">Asamblea General de Miembros</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Documento oficial impreso desde el Sistema de Gestión Financiera Getsemaní &bull; {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
