@extends('imports.layout')

@section('titulo', 'Lote ' . Str::limit($lote->uuid, 8, ''))

@section('contenido')
    @php
        $r = $lote->resumen ?? [];
        $creados = $r['creados'] ?? [];
        $dinero = fn ($n) => '$' . number_format((float) $n, 2);
    @endphp

    <h1>Importación {{ $lote->estado === 'revertido' ? 'revertida' : 'aplicada' }}</h1>
    <p class="sub">
        <span class="mono">{{ $lote->archivo_nombre }}</span> ·
        <span class="etiqueta {{ $lote->estado }}">{{ $lote->estado }}</span> ·
        zona {{ $lote->zona_nombre }}
        @if ($lote->aplicado_at) · aplicada {{ $lote->aplicado_at->format('d/m/Y H:i') }} @endif
        @if ($lote->user) por {{ $lote->user->name }} @endif
    </p>

    @if ($lote->estado === 'revertido')
        <div class="aviso">
            Este lote se revirtió {{ $lote->revertido_at?->format('d/m/Y H:i') }}
            @if ($lote->revertidoPor) por {{ $lote->revertidoPor->name }} @endif.
            Los registros de abajo son la bitácora histórica: los datos ya no están en la base.
        </div>
    @endif

    <h2>Lo que se cargó</h2>

    <div class="tarjetas">
        @foreach ($conteos as $modelo => $total)
            <div class="tarjeta ok">
                <div class="n">{{ number_format($total) }}</div>
                <div class="e">{{ $modelo }}</div>
            </div>
        @endforeach

        @if (! $conteos)
            <div class="tarjeta"><div class="n">0</div><div class="e">sin registros</div></div>
        @endif
    </div>

    @if ($r)
        <div class="rejilla dos" style="margin-top:18px">
            <div class="panel">
                <h2 style="margin-top:0">Resumen del padrón</h2>
                <table>
                    <tr><td>Filas leídas</td><td class="num">{{ number_format($r['filas_totales'] ?? 0) }}</td></tr>
                    <tr><td>Filas con venta</td><td class="num">{{ number_format($r['filas_con_venta'] ?? 0) }}</td></tr>
                    <tr><td>Filas sólo predio</td><td class="num">{{ number_format($r['filas_solo_predio'] ?? 0) }}</td></tr>
                    <tr><td>Predios sin polígono</td><td class="num">{{ number_format($r['predios_sin_poligono'] ?? 0) }}</td></tr>
                    <tr><td>Superficies discrepantes</td><td class="num">{{ number_format($r['superficies_discrepantes'] ?? 0) }}</td></tr>
                </table>
            </div>

            <div class="panel">
                <h2 style="margin-top:0">Importes</h2>
                <table>
                    <tr><td>Costo total</td><td class="num">{{ $dinero($r['importe_total'] ?? 0) }}</td></tr>
                    <tr><td>Pagado</td><td class="num">{{ $dinero($r['importe_pagado'] ?? 0) }}</td></tr>
                    <tr><td>Saldo</td><td class="num"><strong>{{ $dinero($r['importe_saldo'] ?? 0) }}</strong></td></tr>
                </table>
            </div>
        </div>

        @if (! empty($r['avisos']))
            <div class="aviso">
                <strong>Avisos que dejó la simulación</strong>
                <ul>
                    @foreach ($r['avisos'] as $aviso)
                        <li>{{ $aviso }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    <h2>Bitácora · {{ number_format($registros->total()) }} registros</h2>

    <div class="acciones" style="margin-bottom:10px">
        <a class="boton neutro" href="{{ route('imports.padron.registros', $lote) }}">Descargar bitácora (CSV)</a>
        <span class="mini">Cada renglón es un id real en la base. Es lo que se borraría al revertir.</span>
    </div>

    <div class="tabla-scroll">
        <table>
            <thead>
                <tr>
                    <th>Fila Excel</th>
                    <th>Modelo</th>
                    <th class="num">ID</th>
                    <th>Acción</th>
                    <th>Referencia</th>
                    <th>Registrado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($registros as $registro)
                    <tr>
                        <td class="mono">{{ $registro->fila ?? '—' }}</td>
                        <td>{{ class_basename($registro->model_type) }}</td>
                        <td class="num mono">
                            <a href="{{ route('imports.padron.registro', [$lote, $registro]) }}">{{ $registro->model_id }}</a>
                        </td>
                        <td><span class="etiqueta {{ $registro->accion }}">{{ $registro->accion }}</span></td>
                        <td>{{ $registro->referencia ?? '—' }}</td>
                        <td class="mini">{{ $registro->created_at?->format('d/m/Y H:i:s') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $registros->links() }}</div>

    @if ($lote->puedeRevertirse())
        <h2>Revertir</h2>

        <form method="post" action="{{ route('imports.padron.rollback', $lote) }}" class="panel"
              onsubmit="return confirm('Se van a borrar de la base todos los registros creados por esta importación. ¿Continuar?')">
            @csrf

            <p style="margin-top:0">
                Borra en orden inverso todo lo que este lote <strong>creó</strong> (letras, ventas, predios,
                personas y, si aplica, la zona) y deja intacto lo que sólo reutilizó. Si alguien registró
                ventas o abonos sobre estos datos después de importar, la reversión se detiene y te lo dice
                en lugar de romper la información.
            </p>

            <button type="submit" class="peligro">Revertir esta importación</button>
        </form>
    @endif
@endsection
