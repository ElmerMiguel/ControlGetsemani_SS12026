<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Donación - {{ $comprobante->numero }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 10mm 15mm 10mm 15mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.5pt;
            color: #111;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .receipt-container {
            border: 1.5px solid #111;
            padding: 14px 18px;
            position: relative;
            page-break-inside: avoid;
        }
        .anulado-watermark {
            position: absolute;
            top: 40%;
            left: 12%;
            transform: rotate(-25deg);
            font-size: 55pt;
            color: rgba(0, 0, 0, 0.12);
            font-weight: bold;
            letter-spacing: 8px;
            z-index: 1000;
            pointer-events: none;
        }
        .anulado-box {
            border: 1.5px solid #111;
            padding: 5px 8px;
            margin-bottom: 10px;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #111;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        table.header-table td {
            vertical-align: middle;
        }
        .church-name {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .church-temple {
            font-size: 9pt;
            font-weight: bold;
            margin: 2px 0 0 0;
        }
        .church-subtitle {
            font-size: 7.5pt;
            color: #444;
            margin: 1px 0 0 0;
        }
        .receipt-folio-box {
            border: 1.5px solid #111;
            padding: 6px 10px;
            text-align: center;
            background-color: #fff;
        }
        .folio-title {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .folio-number {
            font-size: 12pt;
            font-weight: bold;
            font-family: monospace;
            margin: 2px 0;
        }
        .folio-date {
            font-size: 7.5pt;
            color: #333;
        }
        .receipt-body {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 10px;
        }
        .receipt-body td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 8.5pt;
        }
        .receipt-body tr.border-bottom td {
            border-bottom: 1px dotted #666;
        }
        .td-label {
            width: 25%;
            font-weight: bold;
            color: #222;
            text-transform: uppercase;
            font-size: 7.5pt;
        }
        .td-value {
            width: 75%;
            color: #000;
        }
        .monto-highlight {
            width: 100%;
            border: 1.5px solid #111;
            padding: 8px 12px;
            margin: 8px 0 12px 0;
            background-color: #fafafa;
        }
        .monto-table {
            width: 100%;
            border-collapse: collapse;
        }
        .monto-table td {
            vertical-align: middle;
        }
        .monto-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
        }
        .monto-amount {
            font-size: 15pt;
            font-weight: bold;
            font-family: monospace;
            text-align: right;
            color: #000;
        }
        table.firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            margin-bottom: 8px;
        }
        table.firmas td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 20px;
        }
        .linea-firma {
            border-top: 1px solid #111;
            margin-bottom: 4px;
        }
        .nombre-firmante {
            font-size: 8pt;
            font-weight: bold;
        }
        .cargo-firmante {
            font-size: 7pt;
            color: #444;
            text-transform: uppercase;
        }
        .footer-note {
            border-top: 1px solid #ccc;
            padding-top: 6px;
            margin-top: 8px;
            text-align: center;
            font-size: 7pt;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        @if ($ingreso->trashed())
            <div class="anulado-watermark">ANULADO</div>
            <div class="anulado-box">
                *** DOCUMENTO ANULADO *** &mdash; 
                Anulado el {{ $ingreso->deleted_at?->format('d/m/Y H:i') }} &bull; Motivo: {{ $ingreso->motivo_anulacion ?? 'Sin motivo' }}
            </div>
        @endif

        <table class="header-table">
            <tr>
                <td style="width: 65%;">
                    <div class="church-name">Iglesia Evangélica Nacional Presbiteriana de Guatemala</div>
                    <div class="church-temple">TEMPLO "GETSEMANÍ"</div>
                    <div class="church-subtitle">Sistema Institucional de Tesorería y Control Financiero</div>
                </td>
                <td style="width: 35%;">
                    <div class="receipt-folio-box">
                        <div class="folio-title">Comprobante de Donación</div>
                        <div class="folio-number">{{ $comprobante->numero }}</div>
                        <div class="folio-date">Fecha: {{ $comprobante->generado_en?->format('d/m/Y') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="receipt-body">
            <tr class="border-bottom">
                <td class="td-label">Recibí de:</td>
                <td class="td-value"><strong>{{ $aportante->nombre_completo }}</strong></td>
            </tr>
            <tr class="border-bottom">
                <td class="td-label">Identificación:</td>
                <td class="td-value">
                    CUI/DPI: {{ $aportante->cui_enmascarado }}
                    @if (!empty($aportante->telefono))
                        &nbsp;&bull;&nbsp; Tel: {{ $aportante->telefono }}
                    @endif
                    @if (!empty($aportante->correo))
                        &nbsp;&bull;&nbsp; {{ $aportante->correo }}
                    @endif
                </td>
            </tr>
            <tr class="border-bottom">
                <td class="td-label">Por Concepto de:</td>
                <td class="td-value">
                    <strong>{{ $ingreso->cuenta?->nombre ?? 'Ingreso' }}</strong>
                    @if (!empty($ingreso->cuenta?->codigo))
                        ({{ $ingreso->cuenta->codigo }})
                    @endif
                </td>
            </tr>
            <tr class="border-bottom">
                <td class="td-label">Caja Receptora:</td>
                <td class="td-value">
                    {{ $ingreso->caja?->nombre ?? 'Caja General' }}
                    @if (!empty($ingreso->caja?->codigo))
                        ({{ $ingreso->caja->codigo }})
                    @endif
                </td>
            </tr>
            <tr class="border-bottom">
                <td class="td-label">Fecha de Ingreso:</td>
                <td class="td-value">{{ $ingreso->fecha?->format('d/m/Y') }}</td>
            </tr>
            @if (!empty($ingreso->recibo))
                <tr class="border-bottom">
                    <td class="td-label">Recibo Manual No.:</td>
                    <td class="td-value font-mono">{{ $ingreso->recibo }}</td>
                </tr>
            @endif
            @if (!empty($ingreso->observaciones))
                <tr class="border-bottom">
                    <td class="td-label">Observaciones:</td>
                    <td class="td-value">{{ $ingreso->observaciones }}</td>
                </tr>
            @endif
        </table>

        <div class="monto-highlight">
            <table class="monto-table">
                <tr>
                    <td class="monto-title">Total Aportado:</td>
                    <td class="monto-amount">{{ formato_moneda($ingreso->monto) }}</td>
                </tr>
            </table>
        </div>

        <table class="firmas">
            <tr>
                <td>
                    <div class="linea-firma"></div>
                    <div class="nombre-firmante">{{ $aportante->nombre_completo }}</div>
                    <div class="cargo-firmante">Donante / Aportante</div>
                </td>
                <td>
                    <div class="linea-firma"></div>
                    <div class="nombre-firmante">{{ $comprobante->generador?->name ?? 'Tesorero(a) Responsable' }}</div>
                    <div class="cargo-firmante">Tesorero(a) &bull; Iglesia Getsemaní</div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            "Cada uno dé como propuso en su corazón: no con tristeza, ni por necesidad, porque Dios ama al dador alegre." 2 Corintios 9:7<br>
            Comprobante oficial para control eclesiástico interno &bull; Doc ID #{{ $comprobante->id }} &bull; Emisión: {{ $comprobante->generado_en?->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
