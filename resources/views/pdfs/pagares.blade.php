<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pagarés - {{ $venta->folio }}</title>
    <style>
        @page {
            margin: 0.4cm;
            size: legal portrait;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 7pt;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .pagare-wrapper {
            padding: 2px;
            margin-bottom: 6px;
            page-break-inside: avoid;
        }

        .pagare-container {
            border: 4px double #0d3b66; /* Blue ornate border */
            border-radius: 8px;
            padding: 5px;
            height: 228px;
            position: relative;
            background-color: #fff;
        }

        .inner-content {
            border: 1px solid #0d3b66;
            border-radius: 6px;
            padding: 6px;
            height: 215px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-blue td {
            border: 1px solid #0d3b66;
            text-align: center;
            vertical-align: middle;
            padding: 0;
        }
        
        /* Rounded corners for top header cells */
        .table-blue tr:first-child td:first-child { border-top-left-radius: 6px; }
        .table-blue tr:first-child td:last-child { border-top-right-radius: 6px; }

        .label-header {
            font-weight: bold;
            font-size: 6pt;
            display: block;
            border-bottom: 1px solid #0d3b66;
            padding: 1px 2px;
            background-color: #f0f4f8;
            color: #0d3b66;
        }

        .value-header {
            font-weight: bold;
            font-size: 9pt;
            display: block;
            padding: 2px;
        }

        .date-sub-table td {
            width: 33.3%;
            border-right: 1px solid #0d3b66;
            border-bottom: none !important;
            padding: 0 !important;
        }

        .date-sub-table td:last-child {
            border-right: none;
        }

        .date-label {
            font-size: 5.5pt;
            font-weight: bold;
            display: block;
            border-bottom: 1px solid #0d3b66;
            color: #0d3b66;
        }

        .date-value {
            font-size: 7.5pt;
            font-weight: bold;
            padding: 1px;
        }

        .text-main {
            text-align: justify;
            font-size: 7pt;
            line-height: 1.3;
            margin-top: 4px;
        }

        .monto-letras-centered {
            text-align: center;
            font-size: 8.5pt;
            font-weight: bold;
            border-bottom: 1.5px solid #000;
            margin: 4px 20px;
            padding: 1px;
            text-transform: uppercase;
        }

        .serie-text {
            font-size: 6pt;
            text-align: justify;
            line-height: 1.2;
            margin-top: 3px;
            color: #1a1a1a;
        }

        .signatures-table {
            margin-top: 5px;
        }

        .signature-cell {
            border: 1px solid #0d3b66;
            border-radius: 5px; /* Rounded box */
            height: 60px;
            padding: 0;
            vertical-align: top;
            overflow: hidden;
        }

        .signature-header {
            text-align: center;
            font-weight: bold;
            font-size: 6.5pt;
            border-bottom: 1px solid #0d3b66;
            padding: 2px;
            background-color: #f0f4f8;
            color: #0d3b66;
        }

        .signature-body {
            padding: 4px;
            font-size: 6.5pt;
            line-height: 1.3;
        }

        .firma-box {
            position: absolute;
            bottom: 5px;
            width: 44%;
            text-align: center;
        }

        .firma-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto;
        }

        .firma-label {
            font-size: 6pt;
            font-weight: bold;
            margin-top: 1px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @foreach ($pagares as $index => $pagare)
        <div class="pagare-wrapper">
            <div class="pagare-container">
                <div class="inner-content">
                    <table class="table-blue">
                        <tr>
                            <td style="width: 15%;">
                                <span class="label-header">PAGARÉ No.</span>
                                <span class="value-header">{{ $pagare['numero_display'] }}</span>
                            </td>

                            <td style="width: 25%;">
                                <span class="label-header">FECHA DE EXPEDICIÓN</span>
                                <table class="date-sub-table">
                                    <tr>
                                        <td><span class="date-label">DIA</span><span class="date-value">{{ $pagare['fecha_expedicion']->format('d') }}</span></td>
                                        <td><span class="date-label">MES</span><span class="date-value">{{ $pagare['fecha_expedicion']->format('m') }}</span></td>
                                        <td><span class="date-label">AÑO</span><span class="date-value">{{ $pagare['fecha_expedicion']->format('Y') }}</span></td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width: 25%;">
                                <span class="label-header">FECHA DE VENCIMIENTO</span>
                                <table class="date-sub-table">
                                    <tr>
                                        <td><span class="date-label">DIA</span><span class="date-value">{{ $pagare['fecha_vencimiento']->format('d') }}</span></td>
                                        <td><span class="date-label">MES</span><span class="date-value">{{ $pagare['fecha_vencimiento']->format('m') }}</span></td>
                                        <td><span class="date-label">AÑO</span><span class="date-value">{{ $pagare['fecha_vencimiento']->format('Y') }}</span></td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width: 15%;">
                                <span class="label-header">CONTRATO No.</span>
                                <span class="value-header">{{ $pagare['folio'] }}</span>
                            </td>
                            <td style="width: 20%; border-top-right-radius: 6px;">
                                <span class="label-header">BUENO POR:</span>
                                <table style="width: 100%;">
                                    <tr>
                                        <td style="border: none; width: 25%; font-size: 10pt; font-weight: bold; color: #0d3b66;">$</td>
                                        <td style="border: none; text-align: right; padding-right: 4px;">
                                            <span style="font-size: 9pt; font-weight: bold;">{{ number_format($pagare['monto'], 2) }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <div class="text-main">
                        LA PAZ, B.C.S., EL DIA <b>{{ $pagare['fecha_expedicion']->translatedFormat('d \d\e F \d\e Y') }}</b><br>
                        DEBO(EMOS) Y PAGARE(MOS) INCONDICIONALMENTE POR ESTE PAGARE A LA ORDEN DE <b>{{ strtoupper($acreedor) }}</b>
                        EN ESTA CIUDAD, EL DIA <b>{{ $pagare['fecha_vencimiento']->translatedFormat('d \d\e F \d\e Y') }}</b>
                        LA CANTIDAD DE:
                    </div>

                    <div class="monto-letras-centered">
                        {{ $pagare['monto_letras'] }}
                    </div>

                    <div class="serie-text">
                        VALOR RECIBIDO A MI(NUESTRA) ENTERA SATISFACCIÓN. ESTE PAGARE FORMA PARTE DE UNA SERIE NUMERADA DEL 1 AL {{ $pagare['total_letras'] }}
                        Y TODOS ESTÁN SUJETOS A LA CONDICIÓN DE QUE, AL NO PAGARSE CUALQUIERA DE ELLOS A SU VENCIMIENTO SE CONSIDERARÁN VENCIDOS ANTICIPADAMENTE
                        TODOS LOS QUE LE SIGAN EN NÚMERO, ADEMÁS DE LOS YA VENCIDOS, DESDE LA FECHA DE VENCIMIENTO DE ESTE DOCUMENTO HASTA EL DÍA DE SU LIQUIDACIÓN,
                        CAUSARÁ INTERESES MORATORIOS AL TIPO DEL <b>10%</b> MENSUAL, PAGADERO EN ESTA CIUDAD JUNTAMENTE CON EL PRINCIPAL.
                    </div>

                    <table class="signatures-table">
                        <tr>
                            <td class="signature-cell" style="width: 48%;">
                                <div class="signature-header">NOMBRE Y DIRECCIÓN DEL DEUDOR</div>
                                <div class="signature-body">
                                    {{ strtoupper($venta->comprador->full_name) }}<br>
                                    {{ strtoupper($venta->comprador->calle) }} {{ $venta->comprador->numero_exterior }}, COL. {{ strtoupper($venta->comprador->colonia) }}<br>
                                    {{ strtoupper($venta->comprador->localidad_domicilio ?? 'LA PAZ, B.C.S.') }}, C.P. {{ $venta->comprador->codigo_postal }}<br>
                                    TEL: {{ $venta->comprador->phones->first()->number ?? 'N/A' }}
                                </div>
                                <div class="firma-box" style="left: 12px;">
                                    <div class="firma-line"></div>
                                    <div class="firma-label">FIRMA</div>
                                </div>
                            </td>
                            <td style="width: 4%;"></td>
                            <td class="signature-cell" style="width: 48%;">
                                <div class="signature-header">NOMBRE Y DIRECCIÓN DEL AVAL</div>
                                <div class="signature-body">
                                    {{ strtoupper($venta->aval->full_name ?? 'N/A') }}<br>
                                    {{ strtoupper($venta->aval->calle ?? '') }} {{ $venta->aval->numero_exterior ?? '' }}, COL. {{ strtoupper($venta->aval->colonia ?? '') }}<br>
                                    {{ strtoupper($venta->aval->localidad_domicilio ?? 'LA PAZ, B.C.S.') }}, C.P. {{ $venta->aval->codigo_postal ?? '' }}<br>
                                    TEL: {{ $venta->aval->phones->first()->number ?? 'N/A' }}
                                </div>
                                <div class="firma-box" style="right: 12px;">
                                    <div class="firma-line"></div>
                                    <div class="firma-label">FIRMA</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        @if (($index + 1) % 4 == 0 && ($index + 1) < count($pagares))
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
