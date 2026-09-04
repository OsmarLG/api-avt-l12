@extends('imports.layout')

@section('titulo', 'Nueva importación')

@section('contenido')
    <h1>Importar padrón</h1>
    <p class="sub">
        Sube el Excel del padrón. Primero verás una simulación completa —qué se crearía, qué se
        reutilizaría y qué está mal— y sólo cuando la aceptes se escribe en la base.
    </p>

    @unless ($geojsonExiste)
        <div class="aviso">
            No encuentro el GeoJSON del catastro en <span class="mono">database/seeders/CATASTRO.geojson</span>.
            Los predios se importarían sin polígono y no se verían en el mapa.
        </div>
    @endunless

    <form method="post" action="{{ route('imports.padron.preview') }}" enctype="multipart/form-data">
        @csrf

        <div class="panel">
            <h2 style="margin-top:0">Archivo</h2>

            <label class="campo">
                <span>Excel del padrón (.xlsx)</span>
                <input type="file" name="archivo" accept=".xlsx,.xls" required>
                <small>
                    Se espera una hoja con encabezados en la primera fila:
                    <span class="mono">{{ implode(', ', array_values(\App\Services\Imports\PadronImportService::COLUMNAS)) }}</span>.
                </small>
            </label>

            <label class="campo">
                <span>GeoJSON del catastro</span>
                <input type="text" name="geojson_path" value="{{ old('geojson_path', $opciones['geojson_path']) }}">
                <small>Ruta en el servidor. Se busca por clave catastral normalizada a 12 dígitos.</small>
            </label>
        </div>

        <div class="panel">
            <h2 style="margin-top:0">Zona</h2>

            <div class="rejilla dos">
                <label class="campo">
                    <span>Zona existente</span>
                    <select name="zona_id">
                        <option value="">— crear una nueva —</option>
                        @foreach ($zonas as $zona)
                            <option value="{{ $zona->id }}" @selected(old('zona_id') == $zona->id)>
                                {{ $zona->nombre }}@if ($zona->dueno_nombre) · {{ $zona->dueno_nombre }}@endif
                            </option>
                        @endforeach
                    </select>
                    <small>Si eliges una, se ignoran los dos campos de abajo.</small>
                </label>

                <label class="campo">
                    <span>Nombre de la zona nueva</span>
                    <input type="text" name="zona_nombre" value="{{ old('zona_nombre', $opciones['zona_nombre']) }}">
                </label>

                <label class="campo">
                    <span>Dueño</span>
                    <input type="text" name="zona_dueno" value="{{ old('zona_dueno', $opciones['zona_dueno']) }}">
                </label>

                <label class="campo">
                    <span>Prefijo de folio</span>
                    <input type="text" name="folio_prefijo" value="{{ old('folio_prefijo', $opciones['folio_prefijo']) }}" maxlength="10">
                    <small>El folio queda <span class="mono">PREFIJO-{{ 'número de contrato' }}</span>. Déjalo vacío para usar el contrato tal cual.</small>
                </label>
            </div>
        </div>

        <div class="panel">
            <h2 style="margin-top:0">Opciones</h2>

            <label class="campo" style="max-width:340px">
                <span>Usuario al que se le atribuyen las ventas</span>
                <select name="user_id" required>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" @selected(old('user_id', $opciones['user_id']) == $usuario->id)>
                            {{ $usuario->name }} ({{ $usuario->email }})
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="check">
                <input type="checkbox" name="superficie_desde_excel" value="1" @checked(old('superficie_desde_excel', $opciones['superficie_desde_excel']))>
                <span>
                    Usar la superficie del Excel
                    <small>La del contrato manda sobre la del catastro. El polígono siempre viene del GeoJSON.</small>
                </span>
            </label>

            <label class="check">
                <input type="checkbox" name="importar_telefonos" value="1" @checked(old('importar_telefonos', $opciones['importar_telefonos']))>
                <span>
                    Importar teléfonos
                    <small>Se guardan como teléfono celular de la persona.</small>
                </span>
            </label>

            <label class="check">
                <input type="checkbox" name="permitir_sin_poligono" value="1" @checked(old('permitir_sin_poligono', $opciones['permitir_sin_poligono']))>
                <span>
                    Permitir predios sin polígono
                    <small>Si se desmarca, cualquier clave que no esté en el catastro bloquea la importación.</small>
                </span>
            </label>

            <label class="check">
                <input type="checkbox" name="generar_documentos" value="1" @checked(old('generar_documentos', $opciones['generar_documentos']))>
                <span>
                    Dejar que VentaObserver corra al crear cada venta
                    <small>
                        Hoy el observer tiene comentada la generación de contrato, recibo y pagarés,
                        así que esta casilla no cambia nada. Si se vuelve a activar, apagada evita
                        3 PDF por venta (unos 5 minutos en un padrón de 162 contra ~5 segundos), que
                        para un padrón histórico ya existen en papel. Lo que se genere queda en la
                        bitácora y también se borra al revertir.
                    </small>
                </span>
            </label>
        </div>

        <div class="acciones">
            <button type="submit">Analizar archivo</button>
            <span class="mini">No se escribe nada todavía.</span>
        </div>
    </form>
@endsection
