<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Contratos morosos</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.25;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0;
            font-size: 13pt;
            text-transform: uppercase;
        }
        .header .date {
            float: right;
            font-size: 9pt;
        }
        .clear {
            clear: both;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        th {
            background-color: #e8e8e8;
            border: 1px solid #666;
            padding: 5px 4px;
            font-size: 8pt;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px;
            font-size: 8pt;
            vertical-align: top;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer-note {
            font-size: 8pt;
            margin-top: 8px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
        <span class="date">{{ $fecha_reporte }}</span>
        <div class="clear"></div>
        <h1>Reporte de contratos morosos</h1>
        <div style="font-size: 10pt; margin-top: 4px;">{{ $periodo }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th style="width: 18%;">Cliente</th>
                <th style="width: 12%;">Letras Vencidas</th>
                <th style="width: 60px;">Folio contrato</th>
                <th style="width: 50px;">Lote</th>
                <th style="width: 50px;">Manzana</th>
                <th style="width: 18%;">Vencimiento (primera letra)</th>
                <th style="width: 12%;">Teléfono</th>
                <th style="width: 14%;">Correo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($filas as $index => $f)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $f['nombre'] }}</td>
                    <td>{{ $f['numero_letra'] }}</td>
                    <td class="text-center">{{ $f['folio_contrato'] }}</td>
                    <td class="text-center">{{ $f['lote'] }}</td>
                    <td class="text-center">{{ $f['manzana'] }}</td>
                    <td>{{ $f['fecha_vencimiento'] }}</td>
                    <td>{{ $f['telefono'] }}</td>
                    <td>{{ $f['correo'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No hay registros con letras vencidas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Contratos con letras vencidas: {{ $total_contratos }}.
        Letras vencidas en total: {{ $total_letras_vencidas }}.
    </div>

</body>
</html>
