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

        .header {
            text-align: center;
            border-bottom: 2px solid #555;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #2c3e50;
        }

        .header p {
            margin: 5px 0 0;
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
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
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
                <th>Nacionalidad</th>
                <td>{{ $person->nacionalidad }}</td>
            </tr>
            <tr>
                <th>Estado Civil</th>
                <td>{{ $person->estado_civil }}</td>
            </tr>
            <tr>
                <th>Fecha de Nacimiento</th>
                <td>{{ $person->fecha_nacimiento?->format('d/m/Y') }} ({{ $person->edad }} años)</td>
            </tr>
            <tr>
                <th>Lugar de Nacimiento</th>
                <td>{{ $person->localidad_nacimiento }}, {{ $person->municipio_nacimiento }},
                    {{ $person->estado_nacimiento }}, {{ $person->pais_nacimiento }}
                </td>
            </tr>
            <tr>
                <th>Ocupación o Profesión</th>
                <td>{{ $person->ocupacion_profesion }}</td>
            </tr>
            <tr>
                <th>Dirección</th>
                <td>{{ $person->calle }} {{ $person->numero_exterior }}
                    {{ $person->numero_interior ? 'Int. ' . $person->numero_interior : '' }}
                </td>
            </tr>
            <tr>
                <th>Colonia</th>
                <td>{{ $person->colonia }}</td>
            </tr>
            <tr>
                <th>Ciudad</th>
                <td>{{ $person->ciudad_domicilio }}</td>
            </tr>
            <tr>
                <th>Estado</th>
                <td>{{ $person->estado_domicilio }}</td>
            </tr>
            <tr>
                <th>Teléfono</th>
                <td>{{ $person->phones->first()->number }}</td>
            </tr>
            <tr>
                <th>Celular</th>
                <td>{{ $person->phones->last()->number }}</td>
            </tr>
            <tr>
                <th>Otros</th>
            </tr>
            <tr>
                <th>R.F.C.</th>
                <td>{{ $person->rfc }}</td>
            </tr>
            <tr>
                <th>CURP</th>
                <td>{{ $person->curp }}</td>
            </tr>
            <tr>
                <th>RFC</th>
                <td>{{ $person->rfc }}</td>
            </tr>
            <tr>
                <th>INE</th>
                <td>{{ $person->ine }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $person->emails->first()->email }}</td>
            </tr>
        </table>
    </div>

    <!-- Lugar de Nacimiento y Domicilio -->
    <div class="section">
        <div class="section-title">Ubicación</div>
        <table>
            <tr>
                <th colspan="2" style="background-color: #e9e9e9; text-align: center;">Lugar de Nacimiento</th>
            </tr>
            <tr>
                <th>País / Estado</th>
                <td>{{ $person->pais_nacimiento }}, {{ $person->estado_nacimiento }}</td>
            </tr>
            <tr>
                <th>Municipio / Localidad</th>
                <td>{{ $person->municipio_nacimiento }}, {{ $person->localidad_nacimiento }}</td>
            </tr>

            <tr>
                <th colspan="2" style="background-color: #e9e9e9; text-align: center;">Domicilio Actual</th>
            </tr>
            <tr>
                <th>Dirección</th>
                <td>{{ $person->calle }} {{ $person->numero_exterior }}
                    {{ $person->numero_interior ? 'Int. ' . $person->numero_interior : '' }}
                </td>
            </tr>
            <tr>
                <th>Colonia / CP</th>
                <td>{{ $person->colonia }} CP: {{ $person->codigo_postal }}</td>
            </tr>
            <tr>
                <th>Ubicación</th>
                <td>{{ $person->localidad_domicilio }}, {{ $person->municipio_domicilio }},
                    {{ $person->estado_domicilio }}, {{ $person->pais_domicilio }}
                </td>
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
        @endif
    </div>

    <!-- Referencias -->
    @if($person->references->isNotEmpty())
        <div class="section">
            <div class="section-title">Referencias Personales</div>
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
        </div>
    @endif

    <div class="footer">
        Este documento es un reporte generado automáticamente por el sistema.
    </div>

</body>

</html>