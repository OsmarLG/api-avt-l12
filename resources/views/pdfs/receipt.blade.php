<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recibo de Anticipo - {{ $venta->folio }}</title>
    <style>
        @page {
            margin: 1.5cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }

        .amount-header {
            text-align: right;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 30px;
        }

        .content {
            margin-bottom: 20px;
            text-align: justify;
        }

        b {
            font-weight: bold;
        }

        .payment-plan-table {
            width: 100%;
            border: 2px solid #000;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .payment-plan-table td {
            padding: 4px 10px;
            font-size: 10pt;
            border: none;
        }

        .payment-plan-table .label {
            font-weight: bold;
            width: 30%;
        }

        .payment-plan-table .value {
            width: 25%;
        }

        .payment-plan-table .words {
            width: 45%;
            font-size: 9pt;
        }

        .note {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 30px;
            font-size: 10pt;
        }

        .location-date {
            text-align: right;
            margin-top: 40px;
            font-weight: bold;
            font-size: 11pt;
        }

        .signature-container {
            margin-top: 60px;
            text-align: center;
            width: 100%;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 40%;
            margin: 0 auto 5px auto;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .signature-name {
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10pt;
            line-height: 1.2;
        }
    </style>
</head>

<body>
    <div class="amount-header">Bueno por ${{ number_format($venta->enganche, 2) }}</div>

    <div class="content">
        RECIBÍ DEL <b>C. {{ strtoupper($venta->comprador->fullName ?? 'SIN NOMBRE') }}</b>, LA CANTIDAD DE
        ${{ number_format($venta->enganche, 2) }} ({{ $enganche_letras }}),
    </div>

    <div class="content">
        POR CONCEPTO: <b>ANTICIPO DE LA COMPRAVENTA</b> DEL LOTE IDENTIFICADO CON EL NÚMERO
        <b>{{ $venta->predio->id }}</b>, DE LA MANZANA NÚMERO <b>{{ $venta->predio->manzana ?? 'N/A' }}</b>, CON CLAVE
        CATASTRAL <b>{{ $venta->predio->clave_catastral }}</b>, CON EXTENSIÓN SUPERFICIAL DE
        <b>{{ number_format($venta->predio->sup_terr ?? 0, 2) }} M2</b> ,UBICADO EN
        {{ $venta->predio->ubicacion ?? 'CALLE SIN NOMBRE' }}, DENTRO DEL FRACCIONAMIENTO
        "<b>{{ $venta->predio->zone->nombre ?? 'N/A' }}</b>", EN EL MUNICIPIO DE LA PAZ, ESTADO DE BAJA CALIFORNIA SUR.
        QUEDANDO PENDIENTE LA FIRMA DEL CONTRATO Y PAGARES RESPECTIVAMENTE. <b>EL PLAN DE PAGO SERÁ EL SIGUIENTE:</b>
    </div>

    <table class="payment-plan-table">
        <tr>
            <td class="label">IMPORTE TOTAL:</td>
            <td class="value">$ {{ number_format($venta->costo_lote, 2) }}</td>
            <td class="words">({{ $monto_letras }})</td>
        </tr>
        <tr>
            <td class="label">ANTICIPO:</td>
            <td class="value">$ {{ number_format($venta->enganche, 2) }}</td>
            <td class="words">({{ $enganche_letras }})</td>
        </tr>
        <tr>
            <td class="label">SALDO A PAGAR:</td>
            <td class="value">$ {{ number_format($venta->costo_lote - $venta->enganche, 2) }}</td>
            <td class="words">({{ $saldo_letras }})</td>
        </tr>
        <tr>
            <td class="label">{{ $venta->meses_a_pagar }} MENSUALIDADES DE:</td>
            <td class="value">$
                {{ number_format(($venta->meses_a_pagar > 0) ? ($venta->costo_lote - $venta->enganche) / $venta->meses_a_pagar : 0, 2) }}
            </td>
            <td class="words">({{ $cuota_letras }})</td>
        </tr>
        <tr>
            <td class="label">DIA DE VENCIMINETO:</td>
            <td class="value">{{ optional($venta->fecha_primer_abono)->format('d') ?? '23' }}</td>
            <td class="words">DE CADA MES, COMO SE INDICARÁ EN EL CONTRATO.</td>
        </tr>
    </table>

    <div class="note">
        NOTA: EN CASO DE CANCELACIÓN POR PARTE DEL COMPRADOR, NO SE DEVOLVERÁ EL IMPORTE DEL ANTICIPO , EL CUAL SE
        QUEDARÁ PARA GASTOS DE VENTA Y ADMINISTRACIÓN
    </div>

    <div class="location-date">
        LA PAZ, BAJA CALIFORNIA SUR A {{ $fecha->translatedFormat('d \D\E F \D\E Y') }}.
    </div>

    <div class="signature-container">
        <div class="signature-label">RECIBÍ</div>
        <div style="height: 50px;"></div>
        <div class="signature-line"></div>
        <div class="signature-name">C. MARTIN OJEDA AGUNDEZ</div>
    </div>

    <div class="footer">
        IGNACIO ALTAMIRANO LOCAL 1, ESQ. ISLA CORONADO<br>
        FRACC. RESIDENCIAL LORETO, C.P. 23099<br>
        LA PAZ, B.C.S., TEL. (612) 12-3-27-07
    </div>
</body>

</html>