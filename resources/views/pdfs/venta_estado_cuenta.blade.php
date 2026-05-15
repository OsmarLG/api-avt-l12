<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de cuenta - {{ $folio }}</title>
    <style>
        @page { margin: 1cm; }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
        }
        .text-navy { color: #0f2b5c; }
        .text-green { color: #15803d; }
        .text-red { color: #b91c1c; }
        .text-orange { color: #c2410c; }
        .text-muted { color: #64748b; }
        .font-bold { font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .header-table { width: 100%; margin-bottom: 8px; border: none; }
        .header-table td { border: none; vertical-align: top; padding: 0; }
        .title-main {
            font-size: 22pt;
            font-weight: bold;
            color: #0f2b5c;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .title-sub {
            font-size: 10pt;
            color: #475569;
            margin-top: 4px;
        }
        .brand-box {
            text-align: right;
        }
        .brand-name {
            font-size: 11pt;
            font-weight: bold;
            color: #0f2b5c;
        }
        .brand-sub {
            font-size: 8pt;
            color: #64748b;
        }
        .brand-logo {
            display: block;
            height: 48px;
            width: auto;
            max-width: 140px;
            margin-left: auto;
            margin-bottom: 4px;
        }
        .logo-placeholder {
            display: inline-block;
            width: 36px;
            height: 36px;
            border: 2px solid #0f2b5c;
            border-radius: 6px;
            text-align: center;
            line-height: 32px;
            font-size: 16pt;
            color: #0f2b5c;
            margin-right: 6px;
            vertical-align: middle;
        }
        .tablas-cuenta-apiladas { margin-bottom: 12px; }
        .tablas-cuenta-apiladas .section-box { margin-bottom: 0; padding: 8px 12px 6px 12px; }
        .tablas-cuenta-apiladas .section-box + .section-box {
            margin-top: 0;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 8px 8px;
        }
        .tablas-cuenta-apiladas .section-box:first-child {
            border-radius: 8px 8px 0 0;
            padding-bottom: 4px;
        }
        .tablas-cuenta-apiladas .section-title { margin-bottom: 4px; }
        .divider {
            border: none;
            border-top: 2px solid #0f2b5c;
            margin: 10px 0 12px 0;
        }
        .fecha-emision {
            text-align: right;
            font-size: 9pt;
            color: #334155;
            margin-bottom: 14px;
        }

        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 12px;
            background: #fff;
        }
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #0f2b5c;
            text-transform: uppercase;
            margin: 0 0 8px 0;
            letter-spacing: 0.3px;
        }
        .section-title .icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            background: #0f2b5c;
            color: #fff;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 8pt;
            margin-right: 6px;
        }
        .data-row {
            margin-bottom: 5px;
            font-size: 8.5pt;
        }
        .data-label {
            color: #64748b;
            display: inline-block;
            width: 42%;
        }
        .data-value {
            font-weight: bold;
            color: #0f172a;
        }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-activa, .badge-pagada {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-cancelada {
            background: #fee2e2;
            color: #b91c1c;
        }

        .layout-2col {  width: 100%; border: none; border-collapse: separate; border-spacing: 2px 0; }
        .layout-2col > tbody > tr > td { border: none; width: 50%; vertical-align: top; padding: 0; }

        .plano-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            padding: 6px;
            min-height: 140px;
            text-align: center;
            overflow: hidden;
        }
        .plano-map-img {
            width: 100%;
            max-width: 100%;
            height: auto;
            max-height: 200px;
            border-radius: 4px;
            display: block;
            margin: 0 auto 6px auto;
        }
        .plano-map-fallback {
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            padding: 12px;
            background: #fff;
        }
        .plano-lote {
            display: inline-block;
            border: 2px solid #15803d;
            background: #dcfce7;
            padding: 10px 18px;
            margin: 6px 0;
            font-weight: bold;
            color: #0f2b5c;
            font-size: 9pt;
        }
        .plano-meta {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 4px;
            text-align: left;
            padding: 0 4px 4px 4px;
        }
        .plano-map-notice {
            font-size: 7pt;
            color: #94a3b8;
            margin-top: 4px;
        }

        .fin-grid { width: 100%; border: none; border-collapse: separate; border-spacing: 8px 0; }
        .fin-grid td { border: none; width: 25%; vertical-align: top; padding: 0; }
        .fin-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 8px;
            text-align: center;
            min-height: 72px;
        }
        .fin-card .fin-icon {
            font-size: 14pt;
            margin-bottom: 4px;
        }
        .fin-card .fin-label {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .fin-card .fin-amount {
            font-size: 12pt;
            font-weight: bold;
        }
        .fin-card.precio .fin-amount { color: #15803d; }
        .fin-card.pagado .fin-amount { color: #0f172a; }
        .fin-card.saldo .fin-amount { color: #b91c1c; }
        .fin-card.intereses .fin-amount { color: #c2410c; }

        .ultimo-pago-bar {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 12px;
            background: #f8fafc;
        }
        .ultimo-pago-bar .section-title { margin-bottom: 6px; }
        .ultimo-pago-content { font-size: 9pt; }
        .ultimo-pago-sep {
            display: inline-block;
            margin: 0 16px;
            color: #cbd5e1;
        }

        .resumen-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }
        .resumen-table th {
            background: #0f2b5c;
            color: #fff;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #0f2b5c;
        }
        .resumen-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
        }

        .letras-vencidas-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .letras-vencidas-table th {
            background: #0f2b5c;
            color: #fff;
            padding: 6px 5px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #0f2b5c;
        }
        .letras-vencidas-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
            vertical-align: middle;
        }
        .letras-vencidas-table td.text-left { text-align: left; }
        .letras-vencidas-table tfoot td {
            background: #f1f5f9;
            font-weight: bold;
        }

        .footer-2col { width: 100%; border: none; border-collapse: separate; border-spacing: 10px 0; }
        .footer-2col td { border: none; vertical-align: top; width: 50%; }
        .obs-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px;
            min-height: 70px;
            font-size: 8pt;
            color: #334155;
        }
        .firma-box {
            text-align: center;
            padding-top: 40px;
        }
        .firma-line {
            border-top: 1px solid #0f172a;
            width: 70%;
            margin: 0 auto 6px auto;
        }
        .firma-label {
            font-size: 8pt;
            font-weight: bold;
            color: #475569;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="title-main">ESTADO DE CUENTA</div>
                <div class="title-sub">VENTA DE TERRENO</div>
            </td>
            <td class="brand-box" style="width: 45%;">
                @if(!empty($logo_src))
                    <img src="{{ $logo_src }}" alt="{{ $empresa_nombre }}" class="brand-logo">
                @else
                    <span class="logo-placeholder">&#8962;</span>
                @endif
                <br>
                {{-- <span class="brand-name">{{ $empresa_nombre }}</span><br> --}}
                <span class="brand-sub">Administración de Ventas de bienes raices</span>
            </td>
        </tr>
    </table>

    <hr class="divider">

    <div class="fecha-emision">Fecha de emisión: {{ $fecha_emision }}</div>

    <table class="layout-2col">
        <tr>
            <td>
                <div class="section-box" style="min-height: 255px;">
                    <div class="section-title"> Datos generales de la venta</div>
                    <div class="data-row"><span class="data-label">Folio de venta:</span> <span class="data-value">{{ $folio }}</span></div>
                    <div class="data-row"><span class="data-label">Estado de la venta:</span> <span class="badge {{ $estado_clase }}">{{ $estado_label }}</span></div>
                    <div class="data-row"><span class="data-label">Zona:</span> <span class="data-value">{{ $zona }}</span></div>
                    <div class="data-row"><span class="data-label">Comprador:</span> <span class="data-value">{{ $comprador }}</span></div>
                    <div class="data-row"><span class="data-label">Aval:</span> <span class="data-value">{{ $aval }}</span></div>
                    <div class="data-row"><span class="data-label">Clave catastral:</span> <span class="data-value">{{ $clave_catastral }}</span></div>
                    <div class="data-row"><span class="data-label">Manzana:</span> <span class="data-value">{{ $manzana }}</span></div>
                    <div class="data-row"><span class="data-label">Lote del predio:</span> <span class="data-value">{{ $lote }}</span></div>
                </div>
            </td>
            <td>
                <div class="section-box" style="padding-bottom: 8px; min-height: 255px;">
                    <div class="section-title">Plano del predio</div>
                    <div class="plano-box">
                        @if(!empty($mapa_satellite_src))
                            <img src="{{ $mapa_satellite_src }}" alt="Vista satelital del predio" class="plano-map-img">
                        @else
                            <div class="plano-map-fallback">
                                <div class="plano-lote">{{ $lote }}</div>
                                <div class="plano-map-notice">
                                    El predio no tiene polígono geográfico.
                                </div>
                            </div>
                        @endif
                        <div class="plano-meta">
                            <strong>Lote:</strong> {{ $lote }} &nbsp;|&nbsp;
                            <strong>Manzana:</strong> {{ $manzana }}<br>
                            <strong>Superficie:</strong> {{ $sup_terr }}<br>
                            <strong>Ubicación:</strong> {{ $ubicacion }}
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-box">
        <div class="section-title">Información financiera</div>
        <table class="fin-grid">
            <tr>
                <td>
                    <div class="fin-card precio">
                        <div class="fin-icon">&#128176;</div>
                        <div class="fin-label">Precio de venta</div>
                        <div class="fin-amount">$ {{ number_format($precio_venta, 2) }}</div>
                    </div>
                </td>
                <td>
                    <div class="fin-card pagado">
                        <div class="fin-icon">&#128181;</div>
                        <div class="fin-label">Total pagado</div>
                        <div class="fin-amount">$ {{ number_format($total_pagado, 2) }}</div>
                    </div>
                </td>
                <td>
                    <div class="fin-card saldo">
                        <div class="fin-icon">&#128179;</div>
                        <div class="fin-label">Saldo pendiente</div>
                        <div class="fin-amount">$ {{ number_format($saldo_pendiente, 2) }}</div>
                    </div>
                </td>
                <td>
                    <div class="fin-card intereses">
                        <div class="fin-icon">%</div>
                        <div class="fin-label">Intereses acumulados</div>
                        <div class="fin-amount">$ {{ number_format($intereses_acumulados, 2) }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="layout-2col">
        <tr>
            <td>
                <div class="section-box" style="min-height: 120px;">
                    <div class="section-title">Letra actual</div>
                    <div class="data-row"><span class="data-label">Progreso:</span> <span class="data-value">{{ $progreso }}</span></div>
                    <div class="data-row"><span class="data-label">Letra actual (sin interés):</span> <span class="data-value">$ {{ number_format($letra_sin_interes, 2) }}</span></div>
                    <div class="data-row"><span class="data-label">Interés de la letra actual:</span> <span class="data-value text-orange">$ {{ number_format($interes_letra_actual, 2) }}</span></div>
                    <div class="data-row"><span class="data-label">Letra actual (con interés):</span> <span class="data-value">$ {{ number_format($letra_con_interes, 2) }}</span></div>
                </div>
            </td>
            <td>
                <div class="section-box" style="min-height: 120px;">
                    <div class="section-title">SALDO DE LA VENTA</div>
                    <div class="data-row"><span class="data-label">Saldo sin interés:</span> <span class="data-value">$ {{ number_format($saldo_sin_interes, 2) }}</span></div>
                    <div class="data-row"><span class="data-label">Saldo con interés:</span> <span class="data-value">$ {{ number_format($saldo_con_interes, 2) }}</span></div>
                </div>
            </td>
        </tr>
    </table>

    <div class="ultimo-pago-bar">
        <div class="section-title"> Último pago</div>
        <div class="ultimo-pago-content">
            @if($tiene_ultimo_pago)
                <strong>Fecha:</strong> {{ $ultimo_pago_fecha }}
                <span class="ultimo-pago-sep">|</span>
                <strong>Monto:</strong> $ {{ number_format($ultimo_pago_monto, 2) }}
            @else
                <span class="text-muted">Sin pagos registrados.</span>
            @endif
        </div>
    </div>

  
        <div class="section-box">
            <div class="section-title"> Resumen general</div>
            <table class="resumen-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Zona</th>
                        <th>Comprador</th>
                        <th>Aval</th>
                        <th>C. C.</th>
                        <th>Mz.</th>
                        <th>Lote</th>
                        <th>Letra / Total</th>
                        <th>Total pagado</th>
                        <th>Saldo pend.</th>
                        <th>Intereses</th>
                        <th>Precio venta</th>
                        <th>Estado</th>
                        <th>Último pago</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $resumen['folio'] }}</td>
                        <td>{{ $resumen['zona'] }}</td>
                        <td>{{ $resumen['comprador'] }}</td>
                        <td>{{ $resumen['aval'] }}</td>
                        <td>{{ $resumen['clave_catastral'] }}</td>
                        <td>{{ $resumen['manzana'] }}</td>
                        <td>{{ $resumen['lote'] }}</td>
                        <td>{{ $resumen['letra_progreso'] }}</td>
                        <td>$ {{ number_format($resumen['total_pagado'], 2) }}</td>
                        <td class="text-red">$ {{ number_format($resumen['saldo_pendiente'], 2) }}</td>
                        <td class="text-orange">$ {{ number_format($resumen['intereses_acumulados'], 2) }}</td>
                        <td>$ {{ number_format($resumen['precio_venta'], 2) }}</td>
                        <td class="text-green font-bold">{{ $resumen['estado_label'] }}</td>
                        <td style="font-size: 6.5pt;">{{ $resumen['ultimo_pago'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-box">
            <div class="section-title"> Letras vencidas</div>
            <table class="letras-vencidas-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Cons.</th>
                        <th>Descripción</th>
                        <th style="width: 90px;">Capital</th>
                        <th style="width: 75px;">Interés</th>
                        <th style="width: 95px;">Capital + Interés</th>
                        <th style="width: 110px;">Vencimiento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letras_vencidas as $lv)
                        <tr>
                            <td class="text-center">{{ $lv['consecutivo'] }}</td>
                            <td class="text-left">{{ $lv['descripcion'] }}</td>
                            <td class="text-right">$ {{ number_format($lv['saldo_sin_interes'], 2) }}</td>
                            <td class="text-right text-orange">$ {{ number_format($lv['interes'], 2) }}</td>
                            <td class="text-right font-bold">$ {{ number_format($lv['saldo_con_interes'], 2) }}</td>
                            <td class="text-center">{{ $lv['fecha_vencimiento'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No hay letras vencidas.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($letras_vencidas->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-right">Totales:</td>
                            <td class="text-right">$ {{ number_format($letras_vencidas->sum('saldo_sin_interes'), 2) }}</td>
                            <td class="text-right text-orange">$ {{ number_format($letras_vencidas->sum('interes'), 2) }}</td>
                            <td class="text-right">$ {{ number_format($letras_vencidas->sum('saldo_con_interes'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    

    <table class="footer-2col">
        <tr>
            <td>
                <div class="section-box" style="margin-bottom: 0;">
                    <div class="section-title"><span class="icon">&#128172;</span> Observaciones</div>
                    <div class="obs-box">{{ $observaciones }}</div>
                </div>
            </td>
            <td>
                <div class="firma-box">
                    <div class="firma-line"></div>
                    <div class="firma-label">Firma y sello</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
