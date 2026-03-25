<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de Pagos - Informe General</title>
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
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
        }
        .header .date {
            float: right;
            font-size: 10pt;
        }
        .clear {
            clear: both;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f2f2f2;
            border: 1px solid #999;
            padding: 5px;
            font-size: 9pt;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            font-size: 9pt;
        }
        .zone-header {
            background-color: #888;
            color: white;
            padding: 6px;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .subtotal-row td {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        
        .footer-table {
            border: none;
            margin-top: 30px;
        }
        .footer-table td {
            border: none;
            vertical-align: top;
        }
        .signature-box {
            text-align: center;
            width: 220px;
            margin: 0 auto;
        }
        .signature-role {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 45px;
        }
        .signature-border {
            border-top: 1px solid #000;
            margin-bottom: 5px;
        }
        .signature-name {
            font-size: 9pt;
            font-weight: bold;
        }

        .observations-box {
            border: 1px solid #000;
            height: 60px;
            width: 100%;
            padding: 5px;
            font-size: 8pt;
        }
        .total-section {
            background-color: #ddd;
            padding: 8px;
            font-weight: bold;
            margin-top: 10px;
        }
        .total-label {
            display: inline-block;
            width: 70%;
        }
        .total-value {
            display: inline-block;
            width: 28%;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <span class="date">{{ $fecha_reporte }}</span>
        <div class="clear"></div>
        <h1>BITACORA DE PAGOS</h1>
        <div style="font-size: 11pt; margin-top: 5px;">{{ $periodo }}</div>
    </div>

    @foreach($zonas as $z)
        @if($z['detalles']->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th colspan="6" class="zone-header">{{ $z['responsable'] }} - {{ $z['zona_nombre'] }}</th>
                    </tr>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th style="width: 60px;">CONT</th>
                        <th>CLIENTE</th>
                        <th style="width: 120px;">C. C.</th>
                        <th style="width: 100px;">PAGOS</th>
                        <th style="width: 100px;">IMPORTE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($z['detalles'] as $index => $d)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center">{{ $d['folio'] }}</td>
                            <td>{{ $d['cliente'] }}</td>
                            <td class="text-center">{{ $d['clave_catastral'] }}</td>
                            <td class="text-center">{{ $d['pagos_display'] }}</td>
                            <td class="text-right">$ {{ number_format($d['importe'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="5" class="text-right">Subtotal:</td>
                        <td class="text-right">$ {{ number_format($z['subtotal'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <!-- Mostrar encabezado vacío si así se desea, según la imagen aparecen aunque no tengan pagos -->
            <table>
                <thead>
                    <tr>
                        <th colspan="6" class="zone-header">{{ $z['responsable'] }} - {{ $z['zona_nombre'] }}</th>
                    </tr>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th style="width: 60px;">CONT</th>
                        <th>CLIENTE</th>
                        <th style="width: 120px;">C. C.</th>
                        <th style="width: 100px;">PAGOS</th>
                        <th style="width: 100px;">IMPORTE</th>
                    </tr>
                </thead>
            </table>
        @endif
    @endforeach

    <div class="total-section">
        <div style="font-size: 8pt; margin-bottom: 5px;">TOTAL EN LETRA: {{ $total_letras }}</div>
        <div class="total-label">Total:</div>
        <div class="total-value">$ {{ number_format($total_general, 2) }}</div>
    </div>

    <table class="footer-table">
        <tr>
            <td style="width: 40%;">
                <div class="signature-box">
                    <div class="signature-role">RESPONSABLE</div>
                    <div class="signature-border"></div>
                    <div class="signature-name">ADMINISTRACION</div>
                </div>
            </td>

            <td>
                <div class="font-bold" style="margin-bottom: 5px;">OBSERVACIONES</div>
                <div class="observations-box"></div>
            </td>
        </tr>
    </table>

</body>
</html>
