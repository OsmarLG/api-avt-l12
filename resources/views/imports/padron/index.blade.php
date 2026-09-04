@extends('imports.layout')

@section('titulo', 'Lotes de importación')

@section('contenido')
    <h1>Lotes de importación</h1>
    <p class="sub">Cada carga queda registrada aquí con todo lo que dejó en la base.</p>

    <div class="acciones" style="margin-bottom:16px">
        <a class="boton" href="{{ route('imports.padron.create') }}">Nueva importación</a>
    </div>

    @if ($lotes->isEmpty())
        <div class="panel">
            <p style="margin:0">Todavía no hay importaciones.</p>
        </div>
    @else
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Zona</th>
                        <th>Estado</th>
                        <th class="num">Registros</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lotes as $lote)
                        <tr>
                            <td class="mono">{{ $lote->archivo_nombre }}</td>
                            <td>{{ $lote->zona_nombre ?? '—' }}</td>
                            <td><span class="etiqueta {{ $lote->estado }}">{{ $lote->estado }}</span></td>
                            <td class="num">{{ number_format($lote->registros()->count()) }}</td>
                            <td>{{ $lote->user?->name ?? '—' }}</td>
                            <td class="mini">{{ $lote->created_at->format('d/m/Y H:i') }}</td>
                            <td><a href="{{ route('imports.padron.show', $lote) }}">Ver</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="paginacion">{{ $lotes->links() }}</div>
    @endif
@endsection
