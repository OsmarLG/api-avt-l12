<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de Pagos - {{ $zona->nombre }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.2;
        }
        .header {
            position: relative;
            margin-bottom: 20px;
        }
        .header-logo {
            float: left;
            width: 80px;
        }
        .header-title {
            text-align: right;
            margin-top: 10px;
        }
        .header-title h1 {
            margin: 0;
            font-size: 16pt;
            text-transform: uppercase;
        }
        .header-title .date {
            font-size: 10pt;
            margin-top: 5px;
        }
        .fraccionamiento-name {
            font-size: 18pt;
            font-weight: bold;
            color: #444;
            margin-top: 10px;
        }
        .clear { clear: both; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #f2f2f2;
            border: 1px solid #666;
            padding: 6px;
            font-size: 9pt;
            text-align: center;
        }
        td {
            border: 1px solid #666;
            padding: 5px;
            font-size: 9pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }

        .summary-box {
            background-color: #f2f2f2;
            padding: 8px;
            border: 1px solid #666;
            margin-top: 10px;
        }
        
        .footer-grid {
            margin-top: 20px;
            display: table;
            width: 100%;
        }
        .footer-col {
            display: table-cell;
            border: 1px solid #666;
            padding: 8px;
            vertical-align: top;
        }
        .label {
            font-size: 8pt;
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 3px;
        }
        .value {
            font-size: 10pt;
        }
        .signature-container {
            text-align: center;
            padding-top: 5px;
        }
        .signature-role {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 35px;
            text-transform: uppercase;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-bottom: 5px;
        }
        .signature-name {
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }

    </style>
</head>
<body>

    <div class="header">
        <!-- Placeholder for logo -->
        <div style="float: left; width: 150px;">
            <div style="font-size: 20pt; font-weight: bold;">LOS GIRASOLES</div>
            <div style="font-size: 8pt;">FRACCIONAMIENTO</div>
        </div>
        <div class="header-title">
            <div class="date">{{ $fecha_reporte }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 50px;">Cont.</th>
                <th>Comprador:</th>
                <th style="width: 110px;">C.C.:</th>
                <th style="width: 40px;">Lote:</th>
                <th style="width: 40px;">Mza:</th>
                <th style="width: 80px;">Pagos:</th>
                <th style="width: 90px;">Importe:</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalles as $index => $d)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $d['folio'] }}</td>
                    <td>{{ $d['cliente'] }}</td>
                    <td class="text-center">{{ $d['clave_catastral'] }}</td>
                    <td class="text-center">{{ $d['lote'] }}</td>
                    <td class="text-center">{{ $d['manzana'] }}</td>
                    <td class="text-center">{{ $d['pagos_display'] }}</td>
                    <td class="text-right">$ {{ number_format($d['importe'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <div style="font-size: 8pt; margin-bottom: 5px;">TOTAL EN LETRA: {{ $total_letras }}</div>
        <div style="overflow: hidden;">
            <div style="float: left; font-weight: bold;">Total:</div>
            <div style="float: right; font-weight: bold;">$ {{ number_format($total, 2) }}</div>
        </div>
    </div>

    <div class="footer-grid">
        <div class="footer-col" style="width: 30%;">
            <span class="label">Vendedor:</span>
            <span class="value">{{ $zona->dueno_nombre }}</span>
        </div>
        <div class="footer-col">
            <span class="label">Nota:</span>
            <div style="height: 30px;"></div>
        </div>
    </div>

    <div class="footer-grid">
        <div class="footer-col" style="width: 30%;">
            <span class="label">Fecha:</span>
            <span class="value">{{ $periodo }}</span>
        </div>
        <div class="footer-col" style="width: 30%;">
            <span class="label">Fecha de entrega:</span>
            <div style="height: 20px;"></div>
        </div>
        <div class="footer-col" style="vertical-align: middle;">
            <div class="signature-container">
                <div class="signature-role">RESPONSABLE</div>
                <div class="signature-line"></div>
                <div class="signature-name">ADMINISTRACION</div>
            </div>
        </div>


    </div>

</body>
</html>
