@extends('imports.layout')

@section('titulo', 'Simulación')

@section('contenido')
    <h1>Simulación de importación</h1>
    <p class="sub">
        <span class="mono">{{ $lote->archivo_nombre }}</span> ·
        cargado {{ $lote->created_at->format('d/m/Y H:i') }} ·
        <span class="etiqueta previsualizado">previsualizado</span>
    </p>

    @if ($error)
        <div class="error">{{ $error }}</div>
        <a class="boton neutro" href="{{ route('imports.padron.create') }}">Cargar otro archivo</a>
    @else
        @php
            $r = $analisis['resumen'];
            $filas = $analisis['filas'];
            $bloqueado = $analisis['bloqueos'] !== [];
            $dinero = fn ($n) => '$' . number_format((float) $n, 2);
        @endphp

        @if ($bloqueado)
            <div class="error">
                <strong>No se puede aplicar todavía</strong>
                <ul>
                    @foreach ($analisis['bloqueos'] as $bloqueo)
                        <li>{{ $bloqueo }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($analisis['avisos'])
            <div class="aviso">
                <strong>Revisa antes de aplicar</strong>
                <ul>
                    @foreach ($analisis['avisos'] as $aviso)
                        <li>{{ $aviso }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h2>Qué va a pasar</h2>

        <div class="tarjetas">
            <div class="tarjeta"><div class="n">{{ number_format($r['filas_totales']) }}</div><div class="e">filas leídas</div></div>
            <div class="tarjeta ok"><div class="n">{{ number_format($r['predios_a_crear']) }}</div><div class="e">predios nuevos</div></div>
            <div class="tarjeta @if ($r['predios_a_actualizar']) aviso-n @endif"><div class="n">{{ number_format($r['predios_a_actualizar']) }}</div><div class="e">predios a actualizar</div></div>
            <div class="tarjeta ok"><div class="n">{{ number_format($r['personas_a_crear']) }}</div><div class="e">personas nuevas</div></div>
            <div class="tarjeta"><div class="n">{{ number_format($r['personas_a_reutilizar']) }}</div><div class="e">personas reutilizadas</div></div>
            <div class="tarjeta ok"><div class="n">{{ number_format($r['ventas_a_crear']) }}</div><div class="e">ventas</div></div>
            <div class="tarjeta ok"><div class="n">{{ number_format($r['letras_a_crear']) }}</div><div class="e">letras</div></div>
            <div class="tarjeta @if ($r['filas_con_error']) error-n @endif"><div class="n">{{ number_format($r['filas_con_error']) }}</div><div class="e">filas con error</div></div>
            <div class="tarjeta @if ($r['filas_con_aviso']) aviso-n @endif"><div class="n">{{ number_format($r['filas_con_aviso']) }}</div><div class="e">filas con aviso</div></div>
        </div>

        <div class="rejilla dos" style="margin-top:18px">
            <div class="panel">
                <h2 style="margin-top:0">Zona</h2>
                <ul class="lista-limpia">
                    <li><strong>{{ $analisis['zona']['nombre'] }}</strong> <span class="etiqueta {{ $analisis['zona']['accion'] }}">{{ $analisis['zona']['accion'] }}</span></li>
                    <li class="mini">Dueño: {{ $analisis['zona']['dueno_nombre'] ?: '—' }}</li>
                    <li class="mini">Prefijo de folio: <span class="mono">{{ $analisis['opciones']['folio_prefijo'] ?: '(ninguno)' }}</span></li>
                    <li class="mini">Ventas atribuidas al usuario #{{ $analisis['opciones']['user_id'] }}</li>
                    <li class="mini">Superficie: {{ $analisis['opciones']['superficie_desde_excel'] ? 'la del Excel' : 'la del catastro' }}</li>
                    <li class="mini">
                        Documentos:
                        {{ $analisis['opciones']['generar_documentos']
                            ? 'sí se generan contrato, recibo y pagarés (tarda varios minutos)'
                            : 'no se generan' }}
                    </li>
                </ul>
            </div>

            <div class="panel">
                <h2 style="margin-top:0">Importes</h2>
                <table>
                    <tr><td>Costo total de los lotes</td><td class="num"><strong>{{ $dinero($r['importe_total']) }}</strong></td></tr>
                    <tr><td>Ya pagado según el padrón</td><td class="num">{{ $dinero($r['importe_pagado']) }}</td></tr>
                    <tr><td>Saldo pendiente</td><td class="num"><strong>{{ $dinero($r['importe_saldo']) }}</strong></td></tr>
                </table>
                <p class="mini" style="margin-bottom:0">
                    No se generan pagos ni tickets: el Excel no trae las fechas reales de pago.
                    Las letras cubiertas se crean con estado «pagado» y saldo cero.
                </p>
            </div>

            <div class="panel">
                <h2 style="margin-top:0">Estatus del padrón</h2>
                <table>
                    @foreach ($r['por_estatus'] as $estatus => $conteo)
                        <tr>
                            <td>{{ $estatus }}</td>
                            <td class="mini">{{ \App\Services\Imports\PadronImportService::MAPA_ESTATUS_LEGAL[$estatus] ?? '— sólo financiero —' }}</td>
                            <td class="num">{{ $conteo }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>

            <div class="panel">
                <h2 style="margin-top:0">Geometría</h2>
                <table>
                    <tr><td>Predios con polígono</td><td class="num">{{ $r['filas_totales'] - $r['predios_sin_poligono'] }}</td></tr>
                    <tr><td>Sin polígono en el catastro</td><td class="num">{{ $r['predios_sin_poligono'] }}</td></tr>
                    <tr><td>Superficie distinta (&gt; 1 m²)</td><td class="num">{{ $r['superficies_discrepantes'] }}</td></tr>
                </table>
            </div>
        </div>

        <h2>Fila por fila</h2>

        <div class="acciones" style="margin-bottom:10px">
            <label class="check" style="margin:0">
                <input type="checkbox" id="solo-problemas">
                <span>Mostrar sólo filas con avisos o errores</span>
            </label>
            <a class="boton neutro" href="{{ route('imports.padron.analisis', $lote) }}">Descargar análisis (CSV)</a>
        </div>

        <div class="tabla-scroll" style="max-height:620px;overflow-y:auto">
            <table id="tabla-filas">
                <thead>
                    <tr>
                        <th>Fila</th>
                        <th>Estatus</th>
                        <th>Clave catastral</th>
                        <th>Mz/Lote</th>
                        <th>Folio</th>
                        <th>Comprador</th>
                        <th class="num">M² Excel</th>
                        <th class="num">M² catastro</th>
                        <th>Polígono</th>
                        <th class="num">Costo</th>
                        <th class="num">Anticipo</th>
                        <th class="num">Letras</th>
                        <th class="num">Pagadas</th>
                        <th class="num">Saldo</th>
                        <th>Predio</th>
                        <th>Persona</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $fila)
                        <tr data-problema="{{ $fila['errores'] || $fila['avisos'] ? '1' : '0' }}">
                            <td class="mono">{{ $fila['fila'] }}</td>
                            <td>
                                {{ $fila['estatus'] ?: '—' }}
                                @if ($fila['estatus_legal'])
                                    <span class="etiqueta">{{ $fila['estatus_legal'] }}</span>
                                @endif
                            </td>
                            <td class="mono">{{ $fila['clave_catastral_excel'] ?: '—' }}</td>
                            <td>{{ $fila['manzana'] ?: '—' }} / {{ $fila['lote'] ?: '—' }}</td>
                            <td class="mono">{{ $fila['folio'] ?: '—' }}</td>
                            <td>{{ $fila['comprador'] ?: '—' }}</td>
                            <td class="num">{{ $fila['m2_excel'] !== null ? number_format($fila['m2_excel'], 2) : '—' }}</td>
                            <td class="num">{{ $fila['m2_geojson'] !== null ? number_format($fila['m2_geojson'], 2) : '—' }}</td>
                            <td>{{ $fila['tiene_poligono'] ? 'sí' : 'no' }}</td>
                            <td class="num">{{ $fila['solo_predio'] ? '—' : number_format($fila['cantidad_total'], 2) }}</td>
                            <td class="num">{{ $fila['solo_predio'] ? '—' : number_format($fila['anticipo'], 2) }}</td>
                            <td class="num">{{ $fila['solo_predio'] ? '—' : $fila['letras_a_generar'] }}</td>
                            <td class="num">{{ $fila['solo_predio'] ? '—' : $fila['letras_pagadas'] }}</td>
                            <td class="num">{{ $fila['solo_predio'] ? '—' : number_format($fila['saldo'], 2) }}</td>
                            <td><span class="etiqueta {{ $fila['acciones']['predio'] ?? '' }}">{{ $fila['acciones']['predio'] ?? '—' }}</span></td>
                            <td>
                                @if ($fila['acciones']['persona'] ?? null)
                                    <span class="etiqueta {{ $fila['acciones']['persona'] }}">{{ $fila['acciones']['persona'] }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="envuelve">
                                @foreach ($fila['errores'] as $mensaje)
                                    <div style="color:var(--error)">✕ {{ $mensaje }}</div>
                                @endforeach
                                @foreach ($fila['avisos'] as $mensaje)
                                    <div style="color:var(--aviso)">! {{ $mensaje }}</div>
                                @endforeach
                                @if (! $fila['errores'] && ! $fila['avisos'])
                                    <span class="mini">ok</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h2>Aplicar</h2>

        <form method="post" action="{{ route('imports.padron.commit', $lote) }}" class="panel">
            @csrf

            <p style="margin-top:0">
                Se van a crear <strong>{{ number_format($r['predios_a_crear']) }} predios</strong>,
                <strong>{{ number_format($r['personas_a_crear']) }} personas</strong>,
                <strong>{{ number_format($r['ventas_a_crear']) }} ventas</strong> y
                <strong>{{ number_format($r['letras_a_crear']) }} letras</strong>
                en la base <span class="mono">{{ config('database.connections.'.config('database.default').'.database') }}</span>
                de <span class="mono">{{ config('database.connections.'.config('database.default').'.host') }}</span>.
            </p>

            <label class="check">
                <input type="checkbox" name="confirmacion" value="1" @disabled($bloqueado)>
                <span>
                    Revisé la simulación y quiero aplicarla.
                    <small>Todo queda registrado en este lote y se puede revertir después.</small>
                </span>
            </label>

            <div class="acciones">
                <button type="submit" @disabled($bloqueado)>Aplicar importación</button>
                <a class="boton neutro" href="{{ route('imports.padron.create') }}">Cargar otro archivo</a>
            </div>
        </form>

        <script>
            document.getElementById('solo-problemas').addEventListener('change', function (evento) {
                const soloProblemas = evento.target.checked;
                document.querySelectorAll('#tabla-filas tbody tr').forEach(function (fila) {
                    fila.hidden = soloProblemas && fila.dataset.problema !== '1';
                });
            });
        </script>
    @endif

    {{-- Fuera del @else a propósito: si el archivo ya no está, descartar el lote es
         justo lo único que queda por hacer con él. --}}
    <form method="post" action="{{ route('imports.padron.destroy', $lote) }}"
          onsubmit="return confirm('¿Descartar esta previsualización?')"
          style="margin-top:18px">
        @csrf
        @method('DELETE')
        <button type="submit" class="neutro">Descartar previsualización</button>
    </form>
@endsection
