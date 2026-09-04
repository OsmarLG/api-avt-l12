<?php

use App\Models\ImportBatch;
use App\Models\ImportBatchRecord;
use App\Models\Letra;
use App\Models\Person;
use App\Models\Phone;
use App\Models\Predio;
use App\Models\PredioObservacion;
use App\Models\User;
use App\Models\Venta;
use App\Models\Zone;
use App\Services\Imports\GeoJsonFeatureFinder;
use App\Services\Imports\PadronImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Fixtures
|--------------------------------------------------------------------------
*/

/**
 * Padrón de prueba con los casos que trae el archivo real: escriturado, INSUS,
 * atrasado con saldo, área de donación sin comprador, comprador repetido,
 * anotaciones "Y COP" y entre paréntesis, clave fuera del catastro y clave inválida.
 *
 * @return array<int, array<int, mixed>>
 */
function filasPadronDePrueba(): array
{
    return [
        ['ESTATUS ', 'CON', 'LOTE', 'MANZANA', 'FECHA_CONTRATACION', 'NOMBRE', 'TELEFONO', 'M2',
            'L. PA', 'CAT. TOTAL', 'ANTICIPO', 'MEN', 'PAGARE', 'SALDO', 'CANT. PAG', 'CLAVE CATASTRAL'],

        // Escriturado, liquidado. 30 letras de 1233.3333… → probamos el redondeo.
        ['ESCRITURADO', '5', '1', 'A', '2005-01-13', 'YADIRA DE LOS SANTOS MENDOZA', '612-111-1111', 273.81,
            30, 40000, 3000, 30, 1233.3333333333333, 0, 40000, '103-012-124-001'],

        // INSUS, liquidado.
        ['INSUS', '117', '2', 'A', '2013-04-15', 'VICENTE GOROZAVE CAR', '612-222-2222', 200,
            30, 51000, 3000, 30, 1600, 0, 51000, '103-012-124-002'],

        // Atrasado: 3 de 36 pagadas, queda saldo.
        ['ATRASADO', '118', '3', 'A', '2018-12-01', 'PERLA GPE. ROMERO AGUNDEZ', '612-333-3333', 200,
            3, 105000, 5000, 36, 2777.7777777777778, 91666.666666666672, 13333.333333333334, '103-012-124-003'],

        // Área de donación: sólo predio, sin venta ni letras.
        ['PAGADO', null, '4', 'F', null, 'AREA DE DONACION', null, null,
            null, null, null, null, null, null, null, '103-012-123-004'],

        // Copropietario anotado con "Y COP".
        ['ESCRITURADO', '112', '20', 'D', '2007-07-10', 'SERGIO ERNESTO ESTRADA SANCHEZ  Y COP', '612-444-4444', 200,
            24, 35000, 11000, 24, 1000, 0, 35000, '103-012-121-020'],

        // Segundo titular entre paréntesis.
        ['ESCRITURADO', '116', '4', 'E', '2007-10-03', 'FRANCISCO JAVIER MURILLO LUCERO (ROSA MARIA LOPEZ COTA)', '612-555-5555', 200,
            24, 35000, 7000, 24, 1166.6666666666667, 0, 35000, '103-012-122-004'],

        // Mismo comprador que la primera fila: debe reutilizar la persona.
        ['PAGADO', '200', '7', 'B', '2006-03-01', 'YADIRA DE LOS SANTOS MENDOZA', '612-111-1111', 180,
            12, 24000, 2400, 12, 1800, 0, 24000, '103-012-125-007'],

        // Clave válida pero fuera del catastro: se importa sin polígono.
        ['PAGADO', '201', '8', 'B', '2006-04-01', 'JUAN PEREZ LOPEZ', '612-666-6666', 190,
            10, 20000, 2000, 10, 1800, 0, 20000, '103-012-999-008'],
    ];
}

function escribirPadron(string $ruta, array $filas): string
{
    $libro = new Spreadsheet;
    $hoja = $libro->getActiveSheet();
    $hoja->fromArray($filas, null, 'A1');
    (new Xlsx($libro))->save($ruta);
    $libro->disconnectWorksheets();

    return $ruta;
}

/**
 * @param  array<int, string>  $claves  claves que sí existen en el catastro
 */
function escribirGeoJson(string $ruta, array $claves, bool $conGeometria = false): string
{
    $lineas = ['{', '"type": "FeatureCollection",', '"name": "TEST",', '"features": ['];

    foreach (array_values($claves) as $i => $clave) {
        $feature = [
            'type' => 'Feature',
            'properties' => [
                'gid' => 1000 + $i,
                'clavecatas' => GeoJsonFeatureFinder::normalizar($clave),
                'condicion' => 'VINCULADO',
                'tipo_predi' => 'U',
                'activo' => 'S',
                'propietari' => 'CATASTRO',
                'ubicacion' => 'CALLE PRUEBA '.$i,
                'sup_cons' => 0,
                // La primera clave difiere a propósito de los M2 del Excel.
                'sup_terr' => $i === 0 ? 400.0 : 200.0,
                'vc' => 1000,
                'vt' => 2000,
                'tasa' => 2.5,
                'manzana' => null,
                'area' => 200.0,
            ],
        ];

        if ($conGeometria) {
            $feature['geometry'] = [
                'type' => 'MultiPolygon',
                'coordinates' => [[[[-110.3, 24.1], [-110.3, 24.2], [-110.2, 24.2], [-110.2, 24.1], [-110.3, 24.1]]]],
            ];
        }

        $lineas[] = json_encode($feature).($i === count($claves) - 1 ? '' : ',');
    }

    $lineas[] = ']';
    $lineas[] = '}';

    File::put($ruta, implode("\n", $lineas));

    return $ruta;
}

beforeEach(function () {
    $this->temp = storage_path('app/testing/padron');
    File::ensureDirectoryExists($this->temp);

    $this->xlsx = escribirPadron($this->temp.'/padron.xlsx', filasPadronDePrueba());
    $this->geojson = escribirGeoJson($this->temp.'/catastro.geojson', [
        '103-012-124-001', '103-012-124-002', '103-012-124-003',
        '103-012-123-004', '103-012-121-020', '103-012-122-004', '103-012-125-007',
    ]);

    $this->usuario = User::create([
        'name' => 'Importador', 'username' => 'importador',
        'email' => 'importador@test.local', 'password' => 'secreto123', 'is_active' => true,
    ]);

    $this->opciones = [
        'zona_nombre' => 'Valle Dorado 1ra etapa',
        'zona_dueno' => 'Rodolfo Duarte Villalobos',
        'folio_prefijo' => 'VD1',
        'geojson_path' => $this->geojson,
        'user_id' => $this->usuario->id,
    ];

    $this->servicio = app(PadronImportService::class);
});

afterEach(function () {
    File::deleteDirectory(storage_path('app/testing/padron'));
});

/*
|--------------------------------------------------------------------------
| Normalización de claves catastrales
|--------------------------------------------------------------------------
*/

test('la clave catastral se normaliza a 12 dígitos', function () {
    expect(GeoJsonFeatureFinder::normalizar('103-012-124-001'))->toBe('103012124001')
        ->and(GeoJsonFeatureFinder::normalizar('103012124001'))->toBe('103012124001')
        ->and(GeoJsonFeatureFinder::normalizar(' 103 012 124 001 '))->toBe('103012124001');
});

test('descarta claves que no son 12 dígitos para no provocar falsos positivos', function () {
    // Quitarle los no-dígitos a "AREA COMUN 3" daría "3", que colisiona con decenas de features.
    expect(GeoJsonFeatureFinder::normalizar('AREA COMUN 3'))->toBeNull()
        ->and(GeoJsonFeatureFinder::normalizar('103-012-124'))->toBeNull()
        ->and(GeoJsonFeatureFinder::normalizar(null))->toBeNull()
        ->and(GeoJsonFeatureFinder::normalizar(''))->toBeNull();
});

test('el buscador de geojson encuentra sólo las claves pedidas', function () {
    $encontradas = (new GeoJsonFeatureFinder($this->geojson))
        ->buscar(['103012124001', '103012999008']);

    expect($encontradas)->toHaveCount(1)
        ->and($encontradas)->toHaveKey('103012124001')
        ->and((float) $encontradas['103012124001']['properties']['sup_terr'])->toBe(400.0);
});

test('no se atora con una llave suelta dentro de un texto', function () {
    // El catastro real trae direcciones como "PEDRO CADENA ESQ ARROYO DE LOS POTRILLOS }".
    // Contando llaves a secas el feature nunca "cerraba" y el buffer crecía sin fin.
    $ruta = $this->temp.'/llave-suelta.geojson';

    File::put($ruta, implode("\n", [
        '{', '"type": "FeatureCollection",', '"features": [',
        json_encode(['type' => 'Feature', 'properties' => [
            'clavecatas' => '103012124001', 'ubicacion' => 'ARROYO DE LOS POTRILLOS }', 'sup_terr' => 200.0,
        ]]).',',
        json_encode(['type' => 'Feature', 'properties' => [
            'clavecatas' => '103012124002', 'ubicacion' => 'CALLE NORMAL', 'sup_terr' => 150.0,
        ]]),
        ']', '}',
    ]));

    $encontradas = (new GeoJsonFeatureFinder($ruta))->buscar(['103012124001', '103012124002']);

    // La segunda sólo aparece si el buffer se recuperó de la llave suelta de la primera.
    expect($encontradas)->toHaveCount(2)
        ->and($encontradas['103012124001']['properties']['ubicacion'])->toBe('ARROYO DE LOS POTRILLOS }');
});

test('arma un feature partido en varias líneas', function () {
    $ruta = $this->temp.'/multilinea.geojson';

    File::put($ruta, implode("\n", [
        '{', '"features": [',
        '{ "type": "Feature",',
        '  "properties": { "clavecatas": "103012124001",',
        '                  "sup_terr": 321.5 }',
        '}',
        ']', '}',
    ]));

    $encontradas = (new GeoJsonFeatureFinder($ruta))->buscar(['103012124001']);

    expect($encontradas)->toHaveCount(1)
        ->and($encontradas['103012124001']['properties']['sup_terr'])->toBe(321.5);
});

/*
|--------------------------------------------------------------------------
| Análisis (dry-run)
|--------------------------------------------------------------------------
*/

test('el análisis no escribe nada en la base', function () {
    $this->servicio->analizar($this->xlsx, $this->opciones);

    expect(Zone::count())->toBe(0)
        ->and(Predio::count())->toBe(0)
        ->and(Venta::count())->toBe(0)
        ->and(Person::count())->toBe(0);
});

test('el análisis cuenta bien qué se crearía', function () {
    $a = $this->servicio->analizar($this->xlsx, $this->opciones);

    expect($a['bloqueos'])->toBe([])
        ->and($a['resumen']['filas_totales'])->toBe(8)
        ->and($a['resumen']['filas_con_venta'])->toBe(7)
        ->and($a['resumen']['filas_solo_predio'])->toBe(1)
        ->and($a['resumen']['predios_a_crear'])->toBe(8)
        // 7 compradores, pero uno se repite en dos filas.
        ->and($a['resumen']['personas_a_crear'])->toBe(6)
        ->and($a['resumen']['ventas_a_crear'])->toBe(7)
        // 7 anticipos + 30+30+36+24+24+12+10 mensualidades
        ->and($a['resumen']['letras_a_crear'])->toBe(7 + 166)
        ->and($a['zona']['accion'])->toBe(ImportBatchRecord::ACCION_CREADO);
});

test('separa el estatus legal del estado financiero', function () {
    $a = $this->servicio->analizar($this->xlsx, $this->opciones);
    $porFila = collect($a['filas'])->keyBy('fila');

    expect($porFila[2]['estatus_legal'])->toBe('escriturado')
        ->and($porFila[2]['estado_financiero'])->toBe('pagado')
        ->and($porFila[3]['estatus_legal'])->toBe('insus')
        ->and($porFila[3]['estado_financiero'])->toBe('pagado')
        // Atrasado no es un estado legal: sólo financiero.
        ->and($porFila[4]['estatus_legal'])->toBeNull()
        ->and($porFila[4]['estado_financiero'])->toBe('pagando');
});

test('la fila sin comprador entra sólo como predio', function () {
    $a = $this->servicio->analizar($this->xlsx, $this->opciones);
    $donacion = collect($a['filas'])->firstWhere('fila', 5);

    expect($donacion['solo_predio'])->toBeTrue()
        ->and($donacion['acciones']['venta'])->toBeNull()
        ->and($donacion['acciones']['letras'])->toBe(0)
        ->and($donacion['errores'])->toBe([]);
});

test('avisa de las anotaciones del capturista en los nombres', function () {
    $a = $this->servicio->analizar($this->xlsx, $this->opciones);
    $porFila = collect($a['filas'])->keyBy('fila');

    expect($porFila[6]['comprador'])->toBe('SERGIO ERNESTO ESTRADA SANCHEZ')
        ->and(implode(' ', $porFila[6]['avisos']))->toContain('Y COP')
        ->and($porFila[7]['comprador'])->toBe('FRANCISCO JAVIER MURILLO LUCERO')
        ->and(implode(' ', $porFila[7]['avisos']))->toContain('ROSA MARIA LOPEZ COTA');
});

test('avisa de las claves que no están en el catastro y de las superficies distintas', function () {
    $a = $this->servicio->analizar($this->xlsx, $this->opciones);
    $porFila = collect($a['filas'])->keyBy('fila');

    expect($porFila[9]['tiene_poligono'])->toBeFalse()
        ->and(implode(' ', $porFila[9]['avisos']))->toContain('no está en el GeoJSON')
        // Fila 2: 273.81 m² en el Excel contra 400 del catastro; fila 8: 180 contra 200.
        ->and(implode(' ', $porFila[2]['avisos']))->toContain('Superficie distinta')
        ->and(implode(' ', $porFila[8]['avisos']))->toContain('Superficie distinta')
        ->and($a['resumen']['superficies_discrepantes'])->toBe(2);
});

test('bloquea cuando se le prohíbe importar predios sin polígono', function () {
    $a = $this->servicio->analizar($this->xlsx, $this->opciones + ['permitir_sin_poligono' => false]);

    expect($a['bloqueos'])->not->toBe([])
        ->and(collect($a['filas'])->firstWhere('fila', 9)['errores'])->not->toBe([]);
});

test('detecta el folio que ya existe en la base', function () {
    $zona = Zone::create(['nombre' => 'Otra', 'dueno_nombre' => 'X']);
    $predio = Predio::create(['clave_catastral' => 'AJENA', 'zona_id' => $zona->id, 'estado' => 'disponible']);
    $persona = Person::create(['nombres' => 'A', 'apellido_paterno' => 'B', 'apellido_materno' => 'C']);

    Venta::create([
        'folio' => 'VD1-5', 'person_id' => $persona->id, 'predio_id' => $predio->id,
        'user_id' => $this->usuario->id, 'metodo_pago' => 'meses', 'costo_lote' => 1, 'enganche' => 0,
    ]);

    $a = $this->servicio->analizar($this->xlsx, $this->opciones);

    expect($a['bloqueos'])->not->toBe([])
        ->and(implode(' ', collect($a['filas'])->firstWhere('fila', 2)['errores']))->toContain('VD1-5');
});

test('reutiliza la persona que ya existe en la base', function () {
    Person::create([
        'nombres' => 'YADIRA DE LOS SANTOS',
        'apellido_paterno' => 'MENDOZA',
        'apellido_materno' => '',
    ]);

    // El nombre se parte como: nombres = todo menos los dos últimos tokens.
    $a = $this->servicio->analizar($this->xlsx, $this->opciones);
    $fila = collect($a['filas'])->firstWhere('fila', 2);

    expect($fila['nombre_partido'])->toBe([
        'apellido_materno' => 'MENDOZA',
        'apellido_paterno' => 'SANTOS',
        'nombres' => 'YADIRA DE LOS',
    ]);
});

/*
|--------------------------------------------------------------------------
| Montos de las letras
|--------------------------------------------------------------------------
*/

test('las letras suman exactamente el monto a financiar', function () {
    // 40000 − 3000 = 37000 en 30 letras de 1233.3333…
    $montos = $this->servicio->montosMensualidades(40000, 3000, 1233.3333333333333, 30);

    expect($montos)->toHaveCount(30)
        // Se compara redondeado: sumar 30 floats arrastra error binario, pero la
        // columna es decimal(15,2) y en la base la suma queda exacta.
        ->and(round(array_sum($montos), 2))->toBe(37000.0)
        ->and($montos[0])->toBe(1233.33)
        // La última absorbe los centavos de redondeo.
        ->and($montos[29])->toBe(1233.43);
});

test('el análisis anticipa el saldo real, no el del Excel', function () {
    $a = $this->servicio->analizar($this->xlsx, $this->opciones);
    $atrasada = collect($a['filas'])->firstWhere('fila', 4);

    // El Excel guarda 91,666.666666…; las letras en centavos dan 91,666.66.
    expect($atrasada['saldo_calculado'])->toBe(91666.66)
        ->and($atrasada['saldo'])->not->toBe($atrasada['saldo_calculado']);
});

/*
|--------------------------------------------------------------------------
| Aplicación
|--------------------------------------------------------------------------
*/

test('aplica el padrón completo', function () {
    $lote = $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    expect($lote->estado)->toBe(ImportBatch::ESTADO_APLICADO)
        ->and(Zone::where('nombre', 'Valle Dorado 1ra etapa')->exists())->toBeTrue()
        ->and(Predio::count())->toBe(8)
        ->and(Person::count())->toBe(6)
        ->and(Venta::count())->toBe(7)
        ->and(Letra::count())->toBe(7 + 166)
        ->and(PredioObservacion::count())->toBe(8);

    $venta = Venta::where('folio', 'VD1-5')->firstOrFail();

    expect($venta->estatus_legal)->toBe('escriturado')
        ->and($venta->estado)->toBe('pagado')
        ->and((float) $venta->costo_lote)->toBe(40000.0)
        ->and((float) $venta->saldo_venta)->toBe(0.0)
        ->and($venta->created_at->toDateString())->toBe('2005-01-13')
        ->and($venta->letras()->count())->toBe(31)
        ->and((float) $venta->letras()->sum('monto'))->toBe(40000.0);
});

test('la venta atrasada conserva sus letras pendientes', function () {
    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    $venta = Venta::where('folio', 'VD1-118')->firstOrFail();

    expect($venta->estado)->toBe('pagando')
        ->and($venta->letras()->where('estado', 'pagado')->count())->toBe(4) // anticipo + 3
        ->and($venta->letras()->where('estado', 'pendiente')->count())->toBe(33)
        ->and((float) $venta->saldo_venta)->toBe(91666.66)
        ->and($venta->proxima_letra_id)->not->toBeNull();
});

test('no genera pagos ni tickets del histórico', function () {
    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    expect(DB::table('pagos')->count())->toBe(0)
        ->and(DB::table('abonos')->count())->toBe(0);
});

test('no genera documentos salvo que se pidan', function () {
    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    expect(DB::table('files')->count())->toBe(0);
});

test('la fila sin comprador queda como predio disponible sin venta', function () {
    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    $predio = Predio::where('clave_catastral', '103012123004')->firstOrFail();

    expect($predio->estado)->toBe(Predio::ESTADO_DISPONIBLE)
        ->and($predio->ventas()->count())->toBe(0);
});

test('reutiliza la persona repetida dentro del mismo archivo', function () {
    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    $personas = Person::where('apellido_materno', 'MENDOZA')->get();

    expect($personas)->toHaveCount(1)
        ->and(Venta::where('person_id', $personas->first()->id)->count())->toBe(2)
        // Un solo teléfono, no uno por venta.
        ->and(Phone::where('phoneable_id', $personas->first()->id)->count())->toBe(1);
});

test('guarda la superficie del Excel y no la del catastro', function () {
    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    expect((float) Predio::where('clave_catastral', '103012124001')->value('sup_terr'))->toBe(273.81);
});

test('puede preferir la superficie del catastro', function () {
    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones + ['superficie_desde_excel' => false], $this->usuario->id);

    expect((float) Predio::where('clave_catastral', '103012124001')->value('sup_terr'))->toBe(400.0);
});

test('deja bitácora de cada registro que creó', function () {
    $lote = $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    expect($lote->conteoCreados())->toMatchArray([
        'Zone' => 1,
        'Predio' => 8,
        'PredioObservacion' => 8,
        'Person' => 6,
        'Venta' => 7,
        'Letra' => 173,
    ]);

    $registroVenta = $lote->registros()
        ->where('model_type', Venta::class)
        ->where('referencia', 'VD1-5')
        ->firstOrFail();

    expect($registroVenta->fila)->toBe(2)
        ->and($registroVenta->model_id)->toBe(Venta::where('folio', 'VD1-5')->value('id'));
});

test('se niega a aplicar cuando hay bloqueos', function () {
    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones + ['permitir_sin_poligono' => false], $this->usuario->id);
})->throws(RuntimeException::class, 'No se puede aplicar');

test('importar dos veces el mismo padrón se detiene por folio repetido', function () {
    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);
})->throws(RuntimeException::class, 'No se puede aplicar');

test('reutiliza la zona en vez de duplicarla', function () {
    $zona = Zone::create(['nombre' => 'Valle Dorado 1ra etapa', 'dueno_nombre' => 'Rodolfo Duarte Villalobos']);

    $lote = $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    expect(Zone::count())->toBe(1)
        ->and($lote->zona_id)->toBe($zona->id)
        ->and($lote->registros()->where('model_type', Zone::class)->value('accion'))
        ->toBe(ImportBatchRecord::ACCION_REUTILIZADO);
});

test('guarda el polígono del catastro', function () {
    $geojson = escribirGeoJson($this->temp.'/con-geometria.geojson', ['103-012-124-001'], conGeometria: true);

    $this->servicio->aplicar($this->xlsx, 'padron.xlsx', [...$this->opciones, 'geojson_path' => $geojson], $this->usuario->id);

    expect(Predio::whereNotNull('polygon')->count())->toBe(1)
        ->and(Predio::where('clave_catastral', '103012124001')->value('polygon'))->not->toBeNull();
})->skip(
    fn () => DB::connection()->getDriverName() !== 'mysql',
    'La escritura de geometría necesita MySQL (ST_GeomFromText).'
);

/*
|--------------------------------------------------------------------------
| Reversión
|--------------------------------------------------------------------------
*/

test('revertir deja la base como estaba', function () {
    $antes = [
        'zones' => Zone::count(), 'predios' => Predio::count(), 'people' => Person::count(),
        'ventas' => Venta::withTrashed()->count(), 'letras' => Letra::withTrashed()->count(),
        'phones' => Phone::count(), 'observaciones' => PredioObservacion::count(),
    ];

    $lote = $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);
    $borrados = $this->servicio->revertir($lote, $this->usuario->id);

    expect($borrados)->toMatchArray(['Venta' => 7, 'Letra' => 173, 'Person' => 6, 'Predio' => 8, 'Zone' => 1])
        ->and($lote->fresh()->estado)->toBe(ImportBatch::ESTADO_REVERTIDO)
        ->and([
            'zones' => Zone::count(), 'predios' => Predio::count(), 'people' => Person::count(),
            'ventas' => Venta::withTrashed()->count(), 'letras' => Letra::withTrashed()->count(),
            'phones' => Phone::count(), 'observaciones' => PredioObservacion::count(),
        ])->toBe($antes);
});

test('revertir conserva lo que sólo se reutilizó', function () {
    $zona = Zone::create(['nombre' => 'Valle Dorado 1ra etapa', 'dueno_nombre' => 'Rodolfo Duarte Villalobos']);

    $lote = $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);
    $this->servicio->revertir($lote, $this->usuario->id);

    expect(Zone::find($zona->id))->not->toBeNull();
});

test('no se puede revertir dos veces', function () {
    $lote = $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);
    $this->servicio->revertir($lote, $this->usuario->id);

    $this->servicio->revertir($lote->fresh(), $this->usuario->id);
})->throws(RuntimeException::class, 'no se puede revertir');

test('se niega a revertir si alguien registró una venta sobre un predio importado', function () {
    $lote = $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    $persona = Person::first();
    $predio = Predio::where('clave_catastral', '103012123004')->firstOrFail();

    Venta::create([
        'folio' => 'POSTERIOR-1', 'person_id' => $persona->id, 'predio_id' => $predio->id,
        'user_id' => $this->usuario->id, 'metodo_pago' => 'contado', 'costo_lote' => 1000, 'enganche' => 0,
    ]);

    $this->servicio->revertir($lote, $this->usuario->id);
})->throws(RuntimeException::class, 'sin perder información posterior');

test('se niega a revertir si ya se abonó a las letras importadas', function () {
    $lote = $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);

    $letra = Letra::where('estado', 'pendiente')->firstOrFail();

    $pagoId = DB::table('pagos')->insertGetId([
        'monto' => 100, 'person_id' => $letra->venta->person_id, 'estado' => 'activo',
        'fecha_pago' => now()->toDateString(), 'user_id' => $this->usuario->id,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('abonos')->insert([
        'pago_id' => $pagoId, 'letra_id' => $letra->id, 'monto' => 100,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->servicio->revertir($lote, $this->usuario->id);
})->throws(RuntimeException::class, 'abono');

/*
|--------------------------------------------------------------------------
| Interfaz web
|--------------------------------------------------------------------------
*/

test('el importador exige sesión', function () {
    $this->get('/importador')->assertRedirect('/importador/login');
    $this->get('/importador/padron')->assertRedirect('/importador/login');
});

test('se entra con correo o con usuario', function () {
    $this->post('/importador/login', ['email' => 'importador@test.local', 'password' => 'secreto123'])
        ->assertRedirect('/importador');

    $this->post('/importador/logout');

    $this->post('/importador/login', ['email' => 'importador', 'password' => 'secreto123'])
        ->assertRedirect('/importador');
});

test('rechaza credenciales equivocadas', function () {
    $this->post('/importador/login', ['email' => 'importador@test.local', 'password' => 'mala'])
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

test('la carga deja el lote en previsualización sin escribir nada', function () {
    Storage::fake('local');

    $respuesta = $this->actingAs($this->usuario)->post('/importador/padron/previsualizar', [
        'archivo' => new UploadedFile($this->xlsx, 'padron.xlsx', null, null, true),
        'zona_nombre' => 'Valle Dorado 1ra etapa',
        'zona_dueno' => 'Rodolfo Duarte Villalobos',
        'folio_prefijo' => 'VD1',
        'user_id' => $this->usuario->id,
        'geojson_path' => $this->geojson,
        'superficie_desde_excel' => 1,
        'importar_telefonos' => 1,
        'permitir_sin_poligono' => 1,
    ]);

    $lote = ImportBatch::firstOrFail();

    $respuesta->assertRedirect(route('imports.padron.show', $lote));

    expect($lote->estado)->toBe(ImportBatch::ESTADO_PREVISUALIZADO)
        ->and($lote->resumen['ventas_a_crear'])->toBe(7)
        ->and(Predio::count())->toBe(0)
        ->and(Venta::count())->toBe(0);

    Storage::disk('local')->assertExists('imports/'.$lote->uuid.'.xlsx');
});

test('rechaza un archivo que no es hoja de cálculo', function () {
    $this->actingAs($this->usuario)
        ->post('/importador/padron/previsualizar', [
            'archivo' => UploadedFile::fake()->create('padron.pdf', 10, 'application/pdf'),
            'zona_nombre' => 'X',
            'user_id' => $this->usuario->id,
        ])
        ->assertSessionHasErrors('archivo');

    expect(ImportBatch::count())->toBe(0);
});

test('el flujo completo por la web: previsualizar, aplicar y revertir', function () {
    Storage::fake('local');
    $this->actingAs($this->usuario);

    $this->post('/importador/padron/previsualizar', [
        'archivo' => new UploadedFile($this->xlsx, 'padron.xlsx', null, null, true),
        'zona_nombre' => 'Valle Dorado 1ra etapa',
        'zona_dueno' => 'Rodolfo Duarte Villalobos',
        'folio_prefijo' => 'VD1',
        'user_id' => $this->usuario->id,
        'geojson_path' => $this->geojson,
        'superficie_desde_excel' => 1,
        'importar_telefonos' => 1,
        'permitir_sin_poligono' => 1,
    ]);

    $lote = ImportBatch::firstOrFail();

    $this->get(route('imports.padron.show', $lote))
        ->assertOk()
        ->assertSee('Simulación de importación')
        ->assertSee('Aplicar importación');

    // Sin marcar la casilla no se aplica.
    $this->post(route('imports.padron.commit', $lote), [])->assertSessionHasErrors('confirmacion');
    expect($lote->fresh()->estado)->toBe(ImportBatch::ESTADO_PREVISUALIZADO);

    $this->post(route('imports.padron.commit', $lote), ['confirmacion' => 1])
        ->assertRedirect(route('imports.padron.show', $lote))
        ->assertSessionHas('exito');

    expect($lote->fresh()->estado)->toBe(ImportBatch::ESTADO_APLICADO)
        ->and(Venta::count())->toBe(7)
        ->and(Letra::count())->toBe(173);

    $this->get(route('imports.padron.show', $lote))->assertOk()->assertSee('Importación aplicada');
    $this->get(route('imports.padron.registros', $lote))->assertOk()->assertDownload();

    $this->post(route('imports.padron.rollback', $lote))->assertSessionHas('exito');

    expect($lote->fresh()->estado)->toBe(ImportBatch::ESTADO_REVERTIDO)
        ->and(Venta::count())->toBe(0)
        ->and(Predio::count())->toBe(0);
});

test('no se puede aplicar un lote ya aplicado', function () {
    Storage::fake('local');
    $this->actingAs($this->usuario);

    $lote = $this->servicio->aplicar($this->xlsx, 'padron.xlsx', $this->opciones, $this->usuario->id);
    Storage::disk('local')->put('imports/'.$lote->uuid.'.xlsx', File::get($this->xlsx));

    $this->post(route('imports.padron.commit', $lote), ['confirmacion' => 1])
        ->assertSessionHasErrors('aplicar');
});

test('se puede descartar una previsualización', function () {
    Storage::fake('local');
    $this->actingAs($this->usuario);

    $this->post('/importador/padron/previsualizar', [
        'archivo' => new UploadedFile($this->xlsx, 'padron.xlsx', null, null, true),
        'zona_nombre' => 'Valle Dorado 1ra etapa',
        'user_id' => $this->usuario->id,
        'geojson_path' => $this->geojson,
        'permitir_sin_poligono' => 1,
    ]);

    $lote = ImportBatch::firstOrFail();

    $this->delete(route('imports.padron.destroy', $lote))->assertRedirect(route('imports.padron.index'));

    expect(ImportBatch::count())->toBe(0);
    Storage::disk('local')->assertMissing('imports/'.$lote->uuid.'.xlsx');
});
