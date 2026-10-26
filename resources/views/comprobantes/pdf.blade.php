<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Donación - {{ $comprobante->numero }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .container {
            border: 2px solid #0284c7;
            border-radius: 8px;
            padding: 20px;
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 12px;
            margin-bottom: 16px;
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
            margin: 3px 0;
            color: #0284c7;
            font-weight: 600;
        }
        .header p {
            margin: 0;
            font-size: 8pt;
            color: #64748b;
        }
        .anulado-banner {
            background-color: #fee2e2;
            border: 2px solid #dc2626;
            color: #991b1b;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
        }
        .anulado-watermark {
            position: absolute;
            top: 35%;
            left: 15%;
            transform: rotate(-30deg);
            font-size: 65pt;
            color: rgba(220, 38, 38, 0.22);
            font-weight: bold;
            letter-spacing: 10px;
            z-index: 1000;
            pointer-events: none;
        }
        .meta-box {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }
        .meta-box td {
            vertical-align: top;
            padding: 4px;
        }
        .folio-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: right;
            width: 45%;
        }
        .folio-title {
            font-size: 8pt;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .folio-number {
            font-size: 13pt;
            color: #0284c7;
            font-weight: bold;
        }
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
            background-color: #f1f5f9;
            padding: 5px 8px;
            border-left: 4px solid #0284c7;
            margin-top: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9pt;
        }
        .label {
            width: 28%;
            font-weight: bold;
            color: #475569;
        }
        .val {
            width: 72%;
            color: #0f172a;
        }
        .monto-box {
            margin: 18px 0;
            background-color: #f0fdf4;
            border: 1.5px solid #16a34a;
            border-radius: 6px;
            padding: 12px 16px;
            text-align: center;
        }
        .monto-label {
            font-size: 9pt;
            color: #166534;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .monto-val {
            font-size: 18pt;
            color: #15803d;
            font-weight: bold;
        }
        .firmas-table {
            width: 100%;
            margin-top: 45px;
            border-collapse: collapse;
        }
        .firmas-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 25px;
        }
        .linea-firma {
            border-top: 1px solid #334155;
            margin-bottom: 6px;
        }
        .firma-label {
            font-size: 8pt;
            color: #475569;
        }
        .firma-nombre {
            font-weight: bold;
            color: #0f172a;
            font-size: 8.5pt;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            text-align: center;
            font-size: 7.5pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        @if ($ingreso->trashed())
            <div class="anulado-watermark">ANULADO</div>
            <div class="anulado-banner">
                DOCUMENTO ANULADO<br>
                <span style="font-size: 8pt; font-weight: normal;">
                    Fecha de anulación: {{ $ingreso->deleted_at?->format('d/m/Y H:i') }} | Motivo: {{ $ingreso->motivo_anulacion ?? 'Sin motivo especificado' }}
                </span>
            </div>
        @endif

        <div class="header">
            <h1>Iglesia Evangélica Nacional Presbiteriana de Guatemala</h1>
            <h2>TEMPLO "GETSEMANÍ"</h2>
            <p>Sistema Institucional de Tesorería, Finanzas y Control Contable</p>
        </div>

        <table class="meta-box">
            <tr>
                <td style="width: 55%; vertical-align: middle;">
                    <div style="font-size: 11pt; font-weight: bold; color: #0f172a; text-transform: uppercase;">
                        Comprobante de Donación
                    </div>
                    <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">
                        Comprobante formal para fines eclesiales y contables
                    </div>
                </td>
                <td style="width: 45%;">
                    <div class="folio-box">
                        <div class="folio-title">Número de Comprobante</div>
                        <div class="folio-number">{{ $comprobante->numero }}</div>
                        <div style="font-size: 7.5pt; color: #64748b; margin-top: 3px;">
                            Emisión: {{ $comprobante->generado_en?->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">1. Datos del Aportante</div>
        <table class="data-table">
            <tr>
                <td class="label">Hermano(a) / Donante:</td>
                <td class="val"><strong>{{ $aportante->nombre_completo }}</strong></td>
            </tr>
            <tr>
                <td class="label">Identificación (CUI / DPI):</td>
                <td class="val">{{ $aportante->cui_enmascarado }}</td>
            </tr>
            @if (!empty($aportante->correo) || !empty($aportante->telefono))
                <tr>
                    <td class="label">Contacto:</td>
                    <td class="val">
                        {{ $aportante->correo ?? 'Sin correo' }}
                        @if (!empty($aportante->telefono))
                            | Tel: {{ $aportante->telefono }}
                        @endif
                    </td>
                </tr>
            @endif
        </table>

        <div class="section-title">2. Detalle de la Aportación</div>
        <table class="data-table">
            <tr>
                <td class="label">Fecha de Recepción:</td>
                <td class="val">{{ $ingreso->fecha?->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Caja Receptora:</td>
                <td class="val">{{ $ingreso->caja?->nombre ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Cuenta de Ingreso:</td>
                <td class="val">{{ $ingreso->cuenta?->nombre ?? '—' }} ({{ $ingreso->cuenta?->codigo ?? '' }})</td>
            </tr>
            @if (!empty($ingreso->recibo))
                <tr>
                    <td class="label">Recibo Manual de Ref.:</td>
                    <td class="val">{{ $ingreso->recibo }}</td>
                </tr>
            @endif
            @if (!empty($ingreso->observaciones))
                <tr>
                    <td class="label">Observaciones:</td>
                    <td class="val">{{ $ingreso->observaciones }}</td>
                </tr>
            @endif
        </table>

        <div class="monto-box">
            <div class="monto-label">Monto Aportado</div>
            <div class="monto-val">{{ formato_moneda($ingreso->monto) }}</div>
        </div>

        <table class="firmas-table">
            <tr>
                <td>
                    <div class="linea-firma"></div>
                    <div class="firma-nombre">{{ $aportante->nombre_completo }}</div>
                    <div class="firma-label">Donante / Aportante</div>
                </td>
                <td>
                    <div class="linea-firma"></div>
                    <div class="firma-nombre">{{ $comprobante->generador?->name ?? 'Tesorero(a) Responsable' }}</div>
                    <div class="firma-label">Por Iglesia Getsemaní (Tesorero / Receptor)</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            "Cada uno dé como propuso en su corazón: no con tristeza, ni por necesidad, porque Dios ama al dador alegre." 2 Corintios 9:7<br>
            Comprobante emitido automáticamente por el Sistema de Gestión Financiera · Documento ID #{{ $comprobante->id }} · {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</body>
</html>
