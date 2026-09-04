@extends('imports.layout')

@section('titulo', 'Registro ' . $registro->id)

@section('contenido')
    <h1>{{ class_basename($registro->model_type) }} #{{ $registro->model_id }}</h1>
    <p class="sub">
        <span class="etiqueta {{ $registro->accion }}">{{ $registro->accion }}</span>
        desde la fila {{ $registro->fila ?? '—' }} de
        <span class="mono">{{ $lote->archivo_nombre }}</span> ·
        <a href="{{ route('imports.padron.show', $lote) }}">volver al lote</a>
    </p>

    @if ($modelo === null)
        <div class="aviso">Este registro ya no existe en la base (se borró o se revirtió el lote).</div>
    @else
        <div class="panel">
            <h2 style="margin-top:0">Estado actual</h2>
            <div class="tabla-scroll">
                <table>
                    @foreach ($modelo->attributesToArray() as $campo => $valor)
                        <tr>
                            <th style="width:200px">{{ $campo }}</th>
                            <td class="envuelve mono">{{ is_scalar($valor) || $valor === null ? ($valor ?? 'null') : json_encode($valor, JSON_UNESCAPED_UNICODE) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif

    @if ($registro->datos_previos)
        <div class="panel">
            <h2 style="margin-top:0">Valores anteriores</h2>
            <p class="mini">Esto es lo que se restauraría al revertir el lote.</p>
            <div class="tabla-scroll">
                <table>
                    @foreach ($registro->datos_previos as $campo => $valor)
                        <tr>
                            <th style="width:200px">{{ $campo }}</th>
                            <td class="envuelve mono">{{ is_scalar($valor) || $valor === null ? ($valor ?? 'null') : json_encode($valor, JSON_UNESCAPED_UNICODE) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif
@endsection
