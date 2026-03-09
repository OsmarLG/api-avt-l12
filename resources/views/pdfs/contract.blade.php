<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Contrato de Promesa de Venta - {{ $venta->folio }}</title>
    <style>
        @page {
            margin: 1.5cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.25;
            color: #000;
        }

        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 13pt;
        }

        .folio {
            text-align: right;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 12pt;
        }

        .section-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            font-size: 11pt;
        }

        p {
            margin: 8px 0;
            text-align: justify;
        }

        b {
            font-weight: bold;
        }

        .table-vencimientos {
            width: 45%;
            border-collapse: collapse;
            margin: 15px auto;
        }

        .table-vencimientos th,
        .table-vencimientos td {
            border: 1px solid #000;
            padding: 3px 8px;
            text-align: center;
            font-size: 9pt;
        }

        .table-vencimientos th {
            background-color: #fff;
            font-weight: bold;
        }

        .signatures-container {
            width: 100%;
            margin-top: 40px;
        }

        .signature-box {
            text-align: center;
            vertical-align: top;
            padding-top: 45px;
            width: 50%;
        }

        .signature-box.centered {
            width: 100%;
            padding-left: 20%;
            padding-right: 20%;
        }

        .line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto 5px auto;
        }

        .label {
            font-weight: bold;
            font-size: 9pt;
        }

        .name {
            font-size: 9pt;
        }

        .page-break {
            page-break-after: always;
        }

        .spacer {
            height: 20px;
        }
    </style>
</head>

<body>
    <div class="header">CONTRATO DE PROMESA DE VENTA CON RESERVA DE DOMINIO</div>
    <div class="folio">CONTRATO No. {{ $venta->folio }}</div>

    <p>
        Contrato que celebran por una parte el <b>C. MARTIN OJEDA AGUNDEZ</b>, a quien en lo sucesivo se le denominará
        el Promitente Vendedor y por la otra el <b>C.
            {{ strtoupper($venta->comprador->full_name ?? 'SIN NOMBRE') }}</b>, a quienes en lo sucesivo se le
        denominarán el Promitente Comprador, al tenor de las siguientes:
    </p>

    <div class="section-title">DECLARACIONES</div>

    <p><b>I.- DECLARA EL PROMINENTE VENDEDOR:</b></p>
    <p>
        a).- Ser legítimo propietario y poseedor, de un lote rústico ubicado en
        {{ $venta->predio->ubicacion ?? 'calle Sin Nombre' }}, dentro del fraccionamiento
        <b>"{{ $venta->predio->zone->nombre ?? 'N/A' }}"</b>, identificado con el número
        <b>{{ $venta->predio->id }}</b>, de la manzana número <b>{{ $venta->predio->manzana ?? 'N/A' }}</b>, con clave
        catastral <b>{{ $venta->predio->clave_catastral }}</b>, del Plano Oficial de esta ciudad de La Paz, Estado de
        Baja California Sur, con extensión superficial de <b>{{ number_format($venta->predio->sup_terr ?? 0, 2) }}
            m2.</b>, con las siguientes medidas y colindancias:
    </p>

    <p>b).- Antecedentes de la propiedad. -</p>
    <p>c).- Ser de nacionalidad mexicana, estar al corriente de sus impuestos, indica que tiene como domicilio para oír
        y recibir todo tipo de notificaciones, Calle , Col. , CP. ,en el Municipio de , Estado de , Cel: .</p>

    <p><b>II.- DECLARA EL PROMINENTE COMPRADOR:</b></p>
    <p>
        Ser de nacionalidad {{ $venta->comprador->nacionalidad ?? 'mexicana' }}, estar al corriente de sus impuestos,
        indica que tiene como domicilio para oír y recibir todo tipo de notificaciones,
        {{ $venta->comprador->calle ?? '' }}, Col. {{ $venta->comprador->colonia ?? '' }}, CP.
        {{ $venta->comprador->codigo_postal ?? '' }}, en el Municipio de
        {{ $venta->comprador->municipio_domicilio ?? 'La Paz' }}, Estado de
        {{ $venta->comprador->estado_domicilio ?? 'Baja California Sur' }}, Cel: .
    </p>
    <p>
        Cuenta con Clave Única de Registro de Población <b>{{ $venta->comprador->curp ?? 'N/A' }}</b> y se identifica
        con credencial para votar con fotografía expedida por el Instituto Nacional Electoral, con folio número
        <b>{{ $venta->comprador->ine ?? 'N/A' }}</b>, siendo mayor de edad y con plena capacidad de goce y ejercicio.
    </p>

    <div class="section-title">CLAUSULAS</div>

    <p>
        <b>PRIMERA. -</b> El Promitente Vendedor promete vender y los Promitentes Compradores prometen comprar el bien
        inmueble descrito en la declaración primera, inciso "a", de este contrato, cuyas características medidas y
        colindancias se dan por reproducidas aquí para todos los efectos legales.
    </p>

    <p>
        <b>SEGUNDA. -</b> El precio de la promesa de venta sobre el lote rústico motivo de este contrato es la suma de
        <b>${{ number_format($venta->costo_lote, 2) }} ({{ $monto_letras }})</b>.
    </p>

    <p>
        <b>TERCERA. -</b> El comprador se compromete a pagar el anterior precio como sigue:<br>
        A).- Importe de los productos materia de este contrato ${{ number_format($venta->costo_lote, 2) }}
        ({{ $monto_letras }})<br>
        B).- Como anticipo la cantidad de ${{ number_format($venta->enganche, 2) }} ({{ $enganche_letras }})<br>
        C).- Saldo a pagar de ${{ number_format($venta->costo_lote - $venta->enganche, 2) }} ({{ $saldo_letras }})<br>
        D).- Pagadero en {{ $venta->meses_a_pagar }} ({{ strtoupper($venta->meses_a_pagar_letras ?? 'CANTIDAD') }})
        documentos de
        ${{ number_format(($venta->meses_a_pagar > 0) ? ($venta->costo_lote - $venta->enganche) / $venta->meses_a_pagar : 0, 2) }}
        ({{ $cuota_letras }}) c/u<br>
        E).- Los documentos tendrán su importe y vencimiento como sigue:
    </p>

    <div class="section-title" style="margin-top: 10px;">IMPORTE EN MONEDA NACIONAL</div>

    <table class="table-vencimientos">
        <thead>
            <tr>
                <th>No.</th>
                <th>Importe</th>
                <th>Vencimientos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->letras as $letra)
                <tr>
                    <td>{{ $letra->consecutivo }}. -</td>
                    <td>$ {{ number_format($letra->monto, 2) }}</td>
                    <td>{{ optional($letra->fecha_vencimiento)->translatedFormat('d \d\e F \d\e Y') ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <p>
        <b>CUARTA. -</b> El Promitente Vendedor, recibe dichos Pagares bajo la condición de Salvo Buen Cobro, de acuerdo
        con el Articulo 7 de la Ley General de Títulos y Operaciones de Crédito, por lo que considera pagado el precio
        de esta operación, cuando el ultimo de todos los Pagares con sus intereses moratorios en caso de demora hayan
        sido solventados por el Promitente Comprador.
    </p>

    <p>
        <b>QUINTA. -</b> La presente operación, se hace con reserva de dominio a favor del Promitente Vendedor, mientras
        se establece la escritura, la cual quedara formalizada una vez concluidos los tramites de la lotificación por
        las autoridades correspondientes y también los pagos respectivos, así mismo se aclara que el lote no cuenta con
        ningún servicio público y a su vez el Promitente Vendedor no se obliga a introducirlos.
    </p>

    <p>
        <b>SEXTA. -</b> El Promitente Comprador se obligan al pago de todos los gastos, impuestos, Impuesto Sobre la
        Renta y honorarios que se originen por motivo de la escrituración correspondiente, una vez que se haya
        complementado el pago total del lote.
    </p>

    <p>
        <b>SEPTIMA. -</b> Mientras el precio pactado, no sea cubierto íntegramente y sus accesorios en su caso, de
        conformidad de acuerdo con el artículo 2198 del código civil vigente para el Estado de Baja California Sur, por
        el cual Los Promitentes Compradores, no podrán disponer del bien materia del presente contrato, con la finalidad
        de enajenar, arrendar, habitar, ni ceder de manera alguna derechos sobre el mismo, hasta en tanto sea
        debidamente cubierto el crédito sostenido con el promitente vendedor, cualquier inobservancia a lo arriba
        dispuesto se dará por rescindido el presente contrato sin responsabilidad para el promitente vendedor.
    </p>

    <p>
        <b>OCTAVA. -</b> En el caso de cualquier incumplimiento de los abonos mensuales estipulados no fueran cubiertos
        a su vencimiento El Promitente Vendedor tendrá los siguientes derechos:<br>
        a). -Dar por vencidos todos los plazos y exigir en una sola partida el pago que el Promitente Comprador están
        debiendo más los intereses moratorios a razón de 10% mensuales.<br>
        b). - O bien dar por resuelto el contrato y recoger el lote marcado con el número No.
        <b>{{ $venta->predio->id }}</b>, de la manzana número <b>{{ $venta->predio->manzana ?? 'N/A' }}</b>, con clave
        catastral <b>{{ $venta->predio->clave_catastral }}</b>.
    </p>

    <p>
        <b>NOVENA. -</b> El Promitente Comprador, no quedaran libre de ninguna de las obligaciones que asume, en virtud
        del presente contrato.
    </p>

    <p>
        <b>DECIMA. -</b> Las acciones que competen al Promitente Vendedor se establecen a la elección de éste en el
        lugar que se celebre el presente contrato en el domicilio del Promitente Vendedor por lo que ambas partes lo
        acogen de acuerdo con lo previsto en el Código de Procedimientos Civiles vigente en el Estado de Baja California
        Sur.
    </p>

    <p>
        <b>DECIMA PRIMERA. -</b> Todos los gastos y costos judiciales o extrajudiciales que el Promitente Vendedor tenga
        que erogar para hacer efectivo este contrato serán cubiertos por el Promitente Comprador.
    </p>

    <p>
        <b>DECIMA SEGUNDA. -</b> Las partes se someten para la interpretación de este contrato en lo conducente a lo
        estipulado en el Código Civil vigente para el Estado de Baja California Sur y en lo establecido en el Código
        Civil vigente para la Ciudad de México.
    </p>

    <p>
        <b>DECIMA TERCERA. -</b> Garantiza las obligaciones derivadas de los pagarés del Promitente Comprador el <b>C.
            {{ strtoupper($venta->aval->full_name ?? 'N/A') }}</b>, con calidad de fiador renunciando a los beneficios
        de orden y exclusión según los Artículos 2726 y 2728 del Código Civil vigente en el Estado de Baja California
        Sur.
    </p>

    <p>
        Leído que fue el presente contrato de promesa de venta con reserva de dominio por las partes que en el
        intervinieron y enterados del valor y alcances, legales, de todas y cada una de sus cláusulas, que conforme y
        por considerar que no existe dolo, error o mala fe lo firman en original y copia en la Ciudad de La Paz, Capital
        y puerto del Estado de Baja California Sur, a los <b>{{ $fecha->format('d') }}
            ({{ strtoupper($dia_letras) }})</b> días del mes de <b>{{ $fecha->translatedFormat('F') }}</b> del
        <b>{{ $fecha->format('Y') }} ({{ strtoupper($ano_letras) }})</b>.
    </p>

    <table class="signatures-container">
        <tr>
            <td colspan="2" class="signature-box centered">
                <div class="line"></div>
                <div class="label">EL VENDEDOR</div>
                <div class="name">C. MARTIN OJEDA AGUNDEZ</div>
            </td>
        </tr>
        <tr>
            <td class="signature-box">
                <div class="line"></div>
                <div class="label">EL COMPRADOR</div>
                <div class="name">C. {{ strtoupper($venta->comprador->fullName ?? 'SIN NOMBRE') }}</div>
            </td>
            <td class="signature-box">
                <div class="line"></div>
                <div class="label">EL AVAL</div>
                <div class="name">C. {{ strtoupper($venta->aval->fullName ?? 'SIN NOMBRE') }}</div>
            </td>
        </tr>
        <tr>
            <td class="signature-box">
                <div class="line"></div>
                <div class="label">TESTIGO</div>
            </td>
            <td class="signature-box">
                <div class="line"></div>
                <div class="label">TESTIGO</div>
            </td>
        </tr>
    </table>
</body>

</html>