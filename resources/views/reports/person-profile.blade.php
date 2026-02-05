<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ficha de Persona - {{ $person->nombres }}</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
            line-height: 1.6;
        }

        @page {
            margin: 110px 25px 60px 25px;
        }

        .header {
            position: fixed;
            top: -70px;
            left: 0px;
            right: 0px;
            height: 70px;
            text-align: center;
            border-bottom: 2px solid #555;
            padding-bottom: 5px;
        }

        .header h1 {
            margin: 0;
            color: #2c3e50;
        }

        .header p {
            margin: 15px 0 0;
            font-size: 14px;
            color: #777;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            background-color: #2c3e50;
            color: #fff;
            padding: 5px 10px;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            width: 30%;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -40px;
            left: 0px;
            right: 0px;
            height: 30px;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .footer-content {
            width: 100%;
            display: table;
        }

        .footer-left {
            display: table-cell;
            text-align: left;
            width: 50%;
        }

        .footer-right {
            display: table-cell;
            text-align: right;
            width: 50%;
        }

        .grid-container {
            display: table;
            width: 100%;
        }

        .grid-item {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Ficha de Información Personal</h1>
        <p>Generado el {{ date('d/m/Y H:i') }}</p>
    </div>

    <!-- Datos Personales -->
    <div class="section">
        <div class="section-title">Datos Personales</div>
        <table>
            <tr>
                <th>Nombre Completo</th>
                <td>{{ $person->nombres }} {{ $person->apellido_paterno }} {{ $person->apellido_materno }}</td>
            </tr>
            <tr>
                <th>Fecha de Nacimiento</th>
                <td>{{ $person->fecha_nacimiento?->format('d/m/Y') }} ({{ $person->edad }} años)</td>
            </tr>
            <tr>
                <th>Sexo</th>
                <td>{{ $person->sexo }}</td>
            </tr>
            <tr>
                <th>Nacionalidad</th>
                <td>{{ $person->nacionalidad }}</td>
            </tr>
            <tr>
                <th>Estado Civil</th>
                <td>{{ $person->estado_civil }}</td>
            </tr>
            <tr>
                <th>Ocupación o Profesión</th>
                <td>{{ $person->ocupacion_profesion }}</td>
            </tr>
            <tr>
                <th>R.F.C.</th>
                <td>{{ $person->rfc }}</td>
            </tr>
            <tr>
                <th>C.U.R.P.</th>
                <td>{{ $person->curp }}</td>
            </tr>
            <tr>
                <th>INE</th>
                <td>{{ $person->ine }}</td>
            </tr>
        </table>
    </div>

    <!-- Lugar de Nacimiento -->
    <div class="section">
        <div class="section-title">Lugar de Nacimiento</div>
        <table>
            <tr>
                <th>País</th>
                <td>{{ $person->pais_nacimiento }}</td>
            </tr>
            <tr>
                <th>Estado</th>
                <td>{{ $person->estado_nacimiento }}</td>
            </tr>
            <tr>
                <th>Municipio</th>
                <td>{{ $person->municipio_nacimiento }}</td>
            </tr>
            <tr>
                <th>Localidad</th>
                <td>{{ $person->localidad_nacimiento }}</td>
            </tr>
        </table>
    </div>

    <!-- Domicilio Actual -->
    <div class="section">
        <div class="section-title">Domicilio Actual</div>
        <table>
            <tr>
                <th>Calle y Números</th>
                <td>
                    {{ $person->calle }}
                    @if($person->numero_exterior) Ext. {{ $person->numero_exterior }} @endif
                    @if($person->numero_interior) Int. {{ $person->numero_interior }} @endif
                </td>
            </tr>
            <tr>
                <th>Colonia</th>
                <td>{{ $person->colonia }}</td>
            </tr>
            <tr>
                <th>Código Postal</th>
                <td>{{ $person->codigo_postal }}</td>
            </tr>
            <tr>
                <th>Localidad</th>
                <td>{{ $person->localidad_domicilio }}</td>
            </tr>
            <tr>
                <th>Municipio</th>
                <td>{{ $person->municipio_domicilio }}</td>
            </tr>
            <tr>
                <th>Estado</th>
                <td>{{ $person->estado_domicilio }}</td>
            </tr>
            <tr>
                <th>País</th>
                <td>{{ $person->pais_domicilio }}</td>
            </tr>
        </table>
    </div>

    <!-- Contacto -->
    <div class="section">
        <div class="section-title">Información de Contacto</div>

        @if($person->phones->isNotEmpty())
            <h4>Teléfonos</h4>
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Nota</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($person->phones as $phone)
                        <tr>
                            <td>{{ $phone->number }}</td>
                            <td>{{ $phone->type }}</td>
                            <td>{{ $phone->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <h4>Teléfonos</h4>
            <p style="font-style: italic; color: #777;">No hay teléfonos registrados.</p>
        @endif

        @if($person->emails->isNotEmpty())
            <h4>Correos Electrónicos</h4>
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Nota</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($person->emails as $email)
                        <tr>
                            <td>{{ $email->email }}</td>
                            <td>{{ $email->type }}</td>
                            <td>{{ $email->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <h4>Correos Electrónicos</h4>
            <p style="font-style: italic; color: #777;">No hay correos electrónicos registrados.</p>
        @endif
    </div>

    <!-- Referencias -->
    <div class="section">
        <div class="section-title">Referencias Personales</div>
        @if($person->references->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Parentesco/Relación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($person->references as $reference)
                        <tr>
                            <td>{{ $reference->nombres }}</td>
                            <td>{{ $reference->celular }}</td>
                            <td>{{ $reference->parentesco }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="font-style: italic; color: #777;">No hay referencias personales registradas.</p>
        @endif
    </div>

    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                REF DOC: <strong>{{ $referenceId }}</strong>
            </div>
            <div class="footer-right">
                <!-- Page numbers will be rendered here by script -->
            </div>
        </div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 10;
            $font = $fontMetrics->getFont("Helvetica");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) - 30; // Aligned to right with margin
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>

</body>

</html>