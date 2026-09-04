<?php

namespace App\Services\Imports;

use App\Models\File;
use App\Models\ImportBatch;
use App\Models\ImportBatchRecord;
use App\Models\Letra;
use App\Models\Person;
use App\Models\Phone;
use App\Models\Predio;
use App\Models\PredioObservacion;
use App\Models\Venta;
use App\Models\Zone;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Telescope\Telescope;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use RuntimeException;
use Throwable;

/**
 * Importa un padrón de lotes (Excel) más su geometría (GeoJSON del catastro) a
 * zonas, predios, personas, ventas y letras.
 *
 * Se usa en dos tiempos: `analizar()` no toca la base y devuelve el reporte de lo
 * que pasaría; `aplicar()` vuelve a analizar y escribe dentro de una transacción,
 * dejando en `import_batches` la bitácora de todo lo que creó para poder revertirlo.
 */
class PadronImportService
{
    /** Encabezados esperados en el Excel, ya normalizados por XlsxTableReader. */
    public const COLUMNAS = [
        'estatus' => 'ESTATUS',
        'contrato' => 'CON',
        'lote' => 'LOTE',
        'manzana' => 'MANZANA',
        'fecha_contratacion' => 'FECHA_CONTRATACION',
        'comprador' => 'NOMBRE',
        'telefono' => 'TELEFONO',
        'm2' => 'M2',
        'letras_pagadas' => 'L. PA',
        'cantidad_total' => 'CAT. TOTAL',
        'anticipo' => 'ANTICIPO',
        'mensualidades' => 'MEN',
        'pagare' => 'PAGARE',
        'saldo' => 'SALDO',
        'cantidad_pagada' => 'CANT. PAG',
        'clave_catastral' => 'CLAVE CATASTRAL',
    ];

    /** Sin estas columnas el archivo no es un padrón y no tiene caso seguir. */
    public const COLUMNAS_REQUERIDAS = ['CLAVE CATASTRAL', 'NOMBRE', 'LOTE', 'MANZANA'];

    /** "Escriturado" e "INSUS" son estados legales; el financiero se deduce del saldo. */
    public const MAPA_ESTATUS_LEGAL = [
        'ESCRITURADO' => 'escriturado',
        'INSUS' => 'insus',
        'PAGADO' => null,
        'ATRASADO' => null,
    ];

    /** Centavos de holgura al comparar importes: el Excel guarda pagarés con muchos decimales. */
    private const TOLERANCIA_IMPORTE = 0.01;

    /** Diferencia de superficie (m²) a partir de la cual se marca la fila para revisión. */
    private const TOLERANCIA_SUPERFICIE = 1.0;

    /** Orden de borrado en el rollback: hijos antes que padres. */
    private const ORDEN_ROLLBACK = [
        File::class,
        Letra::class,
        Venta::class,
        PredioObservacion::class,
        Predio::class,
        Phone::class,
        Person::class,
        Zone::class,
    ];

    /** Bitácora acumulada para insertarse por lotes en vez de fila por fila. */
    private array $bitacora = [];

    public function __construct(private readonly XlsxTableReader $reader) {}

    /**
     * @return array<string, mixed>
     */
    public function opcionesPorDefecto(): array
    {
        return [
            'zona_id' => null,
            'zona_nombre' => 'Valle Dorado 1ra etapa',
            'zona_dueno' => 'Rodolfo Duarte Villalobos',
            'folio_prefijo' => 'VD1',
            'geojson_path' => database_path('seeders/CATASTRO.geojson'),
            'user_id' => 1,
            // El catastro y el contrato no siempre coinciden; manda el Excel porque es
            // la superficie que se firmó.
            'superficie_desde_excel' => true,
            'importar_telefonos' => true,
            'permitir_sin_poligono' => true,
            // VentaObserver genera contrato, recibo y pagarés por cada venta. Para un
            // padrón histórico son documentos que ya existen en papel, y renderizarlos
            // cuesta ~1.6 s por venta (el 95 % del tiempo de la importación).
            'generar_documentos' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $opciones
     * @return array<string, mixed>
     */
    public function normalizarOpciones(array $opciones): array
    {
        $opciones = array_merge($this->opcionesPorDefecto(), array_filter(
            $opciones,
            fn ($valor) => $valor !== null && $valor !== ''
        ));

        $opciones['zona_id'] = $opciones['zona_id'] !== null ? (int) $opciones['zona_id'] : null;
        $opciones['user_id'] = (int) $opciones['user_id'];
        $opciones['zona_nombre'] = trim((string) $opciones['zona_nombre']);
        $opciones['zona_dueno'] = trim((string) $opciones['zona_dueno']);
        $opciones['folio_prefijo'] = trim((string) $opciones['folio_prefijo']);

        foreach (['superficie_desde_excel', 'importar_telefonos', 'permitir_sin_poligono', 'generar_documentos'] as $bandera) {
            $opciones[$bandera] = filter_var($opciones[$bandera], FILTER_VALIDATE_BOOL);
        }

        return $opciones;
    }

    // ---------------------------------------------------------------- análisis

    /**
     * Simulación: no escribe nada. Devuelve fila por fila qué se crearía, qué se
     * reutilizaría y qué está bloqueado.
     *
     * @param  array<string, mixed>  $opciones
     * @return array<string, mixed>
     */
    public function analizar(string $xlsxPath, array $opciones = []): array
    {
        $opciones = $this->normalizarOpciones($opciones);

        $tabla = $this->reader->read($xlsxPath);
        $faltantes = array_values(array_diff(self::COLUMNAS_REQUERIDAS, $tabla['headers']));

        if ($faltantes !== []) {
            return [
                'opciones' => $opciones,
                'columnas' => ['encontradas' => $tabla['headers'], 'faltantes' => $faltantes],
                'zona' => null,
                'filas' => [],
                'resumen' => [],
                'avisos' => [],
                'bloqueos' => ['Al archivo le faltan columnas obligatorias: '.implode(', ', $faltantes)],
            ];
        }

        $zona = $this->resolverZona($opciones);
        $filas = array_map(fn (array $fila) => $this->analizarFila($fila, $opciones), $tabla['rows']);

        $filas = $this->resolverGeometria($filas, $opciones);
        $filas = $this->resolverPredios($filas);
        $filas = $this->resolverPersonas($filas);
        $filas = $this->resolverFolios($filas, $opciones);

        $bloqueos = $this->detectarBloqueos($filas);

        return [
            'opciones' => $opciones,
            'columnas' => ['encontradas' => $tabla['headers'], 'faltantes' => []],
            'zona' => $zona,
            'filas' => $filas,
            'resumen' => $this->resumir($filas, $zona),
            'avisos' => $this->avisosGlobales($filas),
            'bloqueos' => $bloqueos,
        ];
    }

    /**
     * @param  array<string, mixed>  $opciones
     * @return array<string, mixed>
     */
    private function resolverZona(array $opciones): array
    {
        if ($opciones['zona_id'] !== null) {
            $zona = Zone::find($opciones['zona_id']);

            if ($zona !== null) {
                return [
                    'id' => $zona->id,
                    'nombre' => $zona->nombre,
                    'dueno_nombre' => $zona->dueno_nombre,
                    'accion' => ImportBatchRecord::ACCION_REUTILIZADO,
                ];
            }
        }

        $existente = Zone::where('nombre', $opciones['zona_nombre'])->first();

        if ($existente !== null) {
            return [
                'id' => $existente->id,
                'nombre' => $existente->nombre,
                'dueno_nombre' => $existente->dueno_nombre,
                'accion' => ImportBatchRecord::ACCION_REUTILIZADO,
            ];
        }

        return [
            'id' => null,
            'nombre' => $opciones['zona_nombre'],
            'dueno_nombre' => $opciones['zona_dueno'],
            'accion' => ImportBatchRecord::ACCION_CREADO,
        ];
    }

    /**
     * @param  array<string, mixed>  $cruda
     * @param  array<string, mixed>  $opciones
     * @return array<string, mixed>
     */
    private function analizarFila(array $cruda, array $opciones): array
    {
        $col = fn (string $llave) => $cruda[self::COLUMNAS[$llave]] ?? null;

        $claveCruda = $this->texto($col('clave_catastral'));
        $claveNormalizada = GeoJsonFeatureFinder::normalizar($claveCruda);
        $compradorCrudo = $this->texto($col('comprador'));
        [$comprador, $avisosNombre] = $this->limpiarNombre($compradorCrudo);

        $estatus = mb_strtoupper($this->texto($col('estatus')) ?? '', 'UTF-8');
        $soloPredio = $comprador === null || $this->esFilaSinVenta($compradorCrudo);

        $fila = [
            'fila' => $cruda['_fila'],
            'estatus' => $estatus,
            'estatus_legal' => self::MAPA_ESTATUS_LEGAL[$estatus] ?? null,
            'clave_catastral_excel' => $claveCruda,
            'clave_catastral' => $claveNormalizada,
            'lote' => $this->texto($col('lote')),
            'manzana' => $this->texto($col('manzana')),
            'contrato' => $this->texto($col('contrato')),
            'comprador_excel' => $compradorCrudo,
            'comprador' => $comprador,
            'telefono' => $this->texto($col('telefono')),
            'fecha_contratacion' => $this->fecha($col('fecha_contratacion')),
            'm2_excel' => $this->numero($col('m2')),
            'm2_geojson' => null,
            'letras_pagadas' => (int) ($this->numero($col('letras_pagadas')) ?? 0),
            'cantidad_total' => $this->numero($col('cantidad_total')) ?? 0.0,
            'anticipo' => $this->numero($col('anticipo')) ?? 0.0,
            'mensualidades' => (int) ($this->numero($col('mensualidades')) ?? 0),
            'pagare' => $this->numero($col('pagare')) ?? 0.0,
            'saldo' => $this->numero($col('saldo')) ?? 0.0,
            'cantidad_pagada' => $this->numero($col('cantidad_pagada')) ?? 0.0,
            'solo_predio' => $soloPredio,
            'tiene_poligono' => false,
            'predio_id' => null,
            'persona_id' => null,
            'folio' => null,
            'acciones' => [],
            'avisos' => $avisosNombre,
            'errores' => [],
        ];

        if ($claveNormalizada === null) {
            $fila['errores'][] = $claveCruda === null
                ? 'Sin clave catastral.'
                : "Clave catastral \"{$claveCruda}\" no tiene 12 dígitos.";
        }

        if (! array_key_exists($estatus, self::MAPA_ESTATUS_LEGAL) && $estatus !== '') {
            $fila['avisos'][] = "Estatus \"{$estatus}\" no reconocido; se importa sin estatus legal.";
        }

        if ($soloPredio) {
            $fila['acciones'] = ['predio' => 'crear', 'persona' => null, 'venta' => null, 'letras' => 0];
            $fila['avisos'][] = 'Fila sin comprador: se importa sólo el predio, sin venta ni letras.';

            return $fila;
        }

        $fila['nombre_partido'] = $this->separarNombre($comprador);
        $fila['estado_financiero'] = $fila['saldo'] > self::TOLERANCIA_IMPORTE ? 'pagando' : 'pagado';

        // Si el anticipo cubre el costo completo no hay nada que financiar: es venta de
        // contado. El padrón a veces igual anota mensualidades con pagaré en cero, y
        // generarlas dejaría letras vencidas de $0.00 apareciendo en el reporte de morosos.
        $fila['es_contado'] = $fila['cantidad_total'] > 0
            && ($fila['cantidad_total'] - $fila['anticipo']) <= self::TOLERANCIA_IMPORTE;

        $fila['letras_a_generar'] = $fila['es_contado'] ? 1 : 1 + $fila['mensualidades'];

        if ($fila['es_contado'] && $fila['mensualidades'] > 0) {
            $fila['avisos'][] = sprintf(
                'Venta de contado: el anticipo cubre el total. Se crea una sola letra tipo «contado» '
                    .'en lugar de %d mensualidades de $0.00.',
                $fila['mensualidades']
            );
        }

        $fila['acciones'] = [
            'predio' => 'crear',
            'persona' => 'crear',
            'venta' => 'crear',
            'letras' => $fila['letras_a_generar'],
        ];

        return $this->validarImportes($fila);
    }

    /**
     * Cuadre aritmético del renglón. Si el Excel no cuadra consigo mismo, las letras
     * que generemos tampoco van a cuadrar, así que se marca antes de escribir nada.
     *
     * @param  array<string, mixed>  $fila
     * @return array<string, mixed>
     */
    private function validarImportes(array $fila): array
    {
        $esperadoPagado = $fila['anticipo'] + $fila['letras_pagadas'] * $fila['pagare'];
        if (abs($esperadoPagado - $fila['cantidad_pagada']) > self::TOLERANCIA_IMPORTE) {
            $fila['avisos'][] = sprintf(
                'Cantidad pagada no cuadra: anticipo + %d letras × pagaré = %s, el Excel dice %s.',
                $fila['letras_pagadas'],
                number_format($esperadoPagado, 2),
                number_format($fila['cantidad_pagada'], 2)
            );
        }

        $esperadoSaldo = $fila['cantidad_total'] - $fila['cantidad_pagada'];
        if (abs($esperadoSaldo - $fila['saldo']) > self::TOLERANCIA_IMPORTE) {
            $fila['avisos'][] = sprintf(
                'Saldo no cuadra: total − pagado = %s, el Excel dice %s.',
                number_format($esperadoSaldo, 2),
                number_format($fila['saldo'], 2)
            );
        }

        $financiar = $fila['cantidad_total'] - $fila['anticipo'];
        if (abs($fila['pagare'] * $fila['mensualidades'] - $financiar) > self::TOLERANCIA_IMPORTE) {
            $fila['avisos'][] = sprintf(
                'Pagaré × mensualidades = %s, pero el monto a financiar es %s. La última letra absorbe la diferencia.',
                number_format($fila['pagare'] * $fila['mensualidades'], 2),
                number_format($financiar, 2)
            );
        }

        // El saldo real será la suma de las letras pendientes, no el número del Excel:
        // el pagaré trae decimales periódicos y las letras se guardan en centavos.
        // En una venta de contado no hay mensualidades, así que el saldo es cero por definición.
        $montos = $fila['es_contado'] ? [] : $this->montosMensualidades(
            $fila['cantidad_total'],
            $fila['anticipo'],
            $fila['pagare'],
            $fila['mensualidades']
        );

        $fila['saldo_calculado'] = round(array_sum(array_slice($montos, $fila['letras_pagadas'])), 2);

        if (abs($fila['saldo_calculado'] - $fila['saldo']) > self::TOLERANCIA_IMPORTE) {
            $fila['avisos'][] = sprintf(
                'El saldo quedará en %s (suma de las letras pendientes) contra %s del Excel: diferencia de redondeo de %s.',
                number_format($fila['saldo_calculado'], 2),
                number_format($fila['saldo'], 2),
                number_format(abs($fila['saldo_calculado'] - $fila['saldo']), 2)
            );
        }

        if ($fila['letras_pagadas'] > $fila['mensualidades']) {
            $fila['errores'][] = sprintf(
                'Hay más letras pagadas (%d) que mensualidades (%d).',
                $fila['letras_pagadas'],
                $fila['mensualidades']
            );
        }

        if ($fila['cantidad_total'] <= 0) {
            $fila['errores'][] = 'El costo del lote es cero o está vacío.';
        }

        if ($fila['fecha_contratacion'] === null) {
            $fila['errores'][] = 'Sin fecha de contratación: no se pueden fechar las letras.';
        }

        return $fila;
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @param  array<string, mixed>  $opciones
     * @return array<int, array<string, mixed>>
     */
    private function resolverGeometria(array $filas, array $opciones): array
    {
        $claves = array_values(array_filter(array_column($filas, 'clave_catastral')));
        $features = (new GeoJsonFeatureFinder($opciones['geojson_path']))->buscar($claves);

        foreach ($filas as $i => $fila) {
            if ($fila['clave_catastral'] === null) {
                continue;
            }

            $feature = $features[$fila['clave_catastral']] ?? null;

            if ($feature !== null) {
                $filas[$i]['feature'] = $feature;
                $filas[$i]['tiene_poligono'] = isset($feature['geometry']['type']);
                $filas[$i]['m2_geojson'] = GeoJsonFeatureFinder::superficie($feature);
            }

            if (! $filas[$i]['tiene_poligono']) {
                $mensaje = $feature === null
                    ? 'La clave no está en el GeoJSON del catastro.'
                    : 'La clave está en el catastro pero el feature no trae geometría.';

                if ($opciones['permitir_sin_poligono']) {
                    $filas[$i]['avisos'][] = $mensaje.' El predio se crea sin polígono y no se verá en el mapa.';
                } else {
                    $filas[$i]['errores'][] = $mensaje;
                }
            }

            if ($feature === null) {
                continue;
            }

            $m2Excel = $fila['m2_excel'];
            $m2Geo = $filas[$i]['m2_geojson'];

            if ($m2Excel !== null && $m2Geo !== null && abs($m2Excel - $m2Geo) > self::TOLERANCIA_SUPERFICIE) {
                $filas[$i]['avisos'][] = sprintf(
                    'Superficie distinta: Excel %s m² vs catastro %s m². Se guarda %s.',
                    number_format($m2Excel, 2),
                    number_format($m2Geo, 2),
                    $opciones['superficie_desde_excel'] ? 'la del Excel' : 'la del catastro'
                );
            }
        }

        return $filas;
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @return array<int, array<string, mixed>>
     */
    private function resolverPredios(array $filas): array
    {
        $claves = array_values(array_filter(array_column($filas, 'clave_catastral')));

        $existentes = Predio::whereIn('clave_catastral', $claves)
            ->pluck('id', 'clave_catastral');

        $vistas = [];

        foreach ($filas as $i => $fila) {
            $clave = $fila['clave_catastral'];

            if ($clave === null) {
                continue;
            }

            if (isset($vistas[$clave])) {
                $filas[$i]['errores'][] = "Clave catastral repetida en el archivo (también en la fila {$vistas[$clave]}).";

                continue;
            }

            $vistas[$clave] = $fila['fila'];

            if (isset($existentes[$clave])) {
                $filas[$i]['predio_id'] = (int) $existentes[$clave];
                $filas[$i]['acciones']['predio'] = 'actualizar';
                $filas[$i]['avisos'][] = "El predio ya existe (#{$existentes[$clave]}): se actualiza en lugar de crearse.";
            }
        }

        return $filas;
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @return array<int, array<string, mixed>>
     */
    private function resolverPersonas(array $filas): array
    {
        $enEsteArchivo = [];

        foreach ($filas as $i => $fila) {
            if ($fila['solo_predio'] || empty($fila['nombre_partido'])) {
                continue;
            }

            $partes = $fila['nombre_partido'];
            $llave = mb_strtoupper(implode('|', $partes), 'UTF-8');

            if (isset($enEsteArchivo[$llave])) {
                $filas[$i]['acciones']['persona'] = 'reutilizar';
                $filas[$i]['persona_repetida_en_fila'] = $enEsteArchivo[$llave];

                continue;
            }

            $enEsteArchivo[$llave] = $fila['fila'];

            $coincidencias = Person::where('nombres', $partes['nombres'])
                ->where('apellido_paterno', $partes['apellido_paterno'])
                ->where('apellido_materno', $partes['apellido_materno'])
                ->pluck('id');

            if ($coincidencias->isEmpty()) {
                continue;
            }

            $filas[$i]['persona_id'] = (int) $coincidencias->first();
            $filas[$i]['acciones']['persona'] = 'reutilizar';

            $filas[$i]['avisos'][] = $coincidencias->count() > 1
                ? 'Hay '.$coincidencias->count()." personas con este nombre (#{$coincidencias->implode(', #')}). Se usará la primera: verifícalo."
                : "Ya existe una persona con este nombre (#{$coincidencias->first()}): se reutiliza.";
        }

        return $filas;
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @param  array<string, mixed>  $opciones
     * @return array<int, array<string, mixed>>
     */
    private function resolverFolios(array $filas, array $opciones): array
    {
        $prefijo = $opciones['folio_prefijo'];
        $propuestos = [];

        foreach ($filas as $i => $fila) {
            if ($fila['solo_predio']) {
                continue;
            }

            $contrato = $fila['contrato'] !== null ? (string) $fila['contrato'] : (string) $fila['fila'];
            $folio = $prefijo !== '' ? "{$prefijo}-{$contrato}" : $contrato;

            $filas[$i]['folio'] = $folio;

            if (isset($propuestos[$folio])) {
                $filas[$i]['errores'][] = "Folio {$folio} repetido en el archivo (también en la fila {$propuestos[$folio]}).";

                continue;
            }

            $propuestos[$folio] = $fila['fila'];
        }

        $ocupados = Venta::withTrashed()
            ->whereIn('folio', array_keys($propuestos))
            ->pluck('id', 'folio');

        foreach ($filas as $i => $fila) {
            if ($fila['folio'] !== null && isset($ocupados[$fila['folio']])) {
                $filas[$i]['errores'][] = "El folio {$fila['folio']} ya lo usa la venta #{$ocupados[$fila['folio']]}.";
            }
        }

        return $filas;
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @return array<int, string>
     */
    private function detectarBloqueos(array $filas): array
    {
        $conError = array_values(array_filter($filas, fn ($f) => $f['errores'] !== []));

        if ($conError === []) {
            return [];
        }

        return [sprintf(
            '%d fila(s) con errores que impiden importar: %s.',
            count($conError),
            implode(', ', array_map(fn ($f) => 'fila '.$f['fila'], array_slice($conError, 0, 15)))
                .(count($conError) > 15 ? ', …' : '')
        )];
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @param  array<string, mixed>|null  $zona
     * @return array<string, mixed>
     */
    private function resumir(array $filas, ?array $zona): array
    {
        $ventas = array_values(array_filter($filas, fn ($f) => ! $f['solo_predio'] && $f['errores'] === []));

        $porEstatus = [];
        foreach ($filas as $fila) {
            $clave = $fila['estatus'] !== '' ? $fila['estatus'] : '(sin estatus)';
            $porEstatus[$clave] = ($porEstatus[$clave] ?? 0) + 1;
        }

        $personasNuevas = [];
        foreach ($ventas as $fila) {
            if (($fila['acciones']['persona'] ?? null) === 'crear') {
                $personasNuevas[mb_strtoupper(implode('|', $fila['nombre_partido']), 'UTF-8')] = true;
            }
        }

        return [
            'zona' => $zona,
            'filas_totales' => count($filas),
            'filas_con_venta' => count($ventas),
            'filas_solo_predio' => count(array_filter($filas, fn ($f) => $f['solo_predio'])),
            'filas_con_error' => count(array_filter($filas, fn ($f) => $f['errores'] !== [])),
            'filas_con_aviso' => count(array_filter($filas, fn ($f) => $f['avisos'] !== [])),
            'predios_a_crear' => count(array_filter($filas, fn ($f) => ($f['acciones']['predio'] ?? null) === 'crear' && $f['errores'] === [])),
            'predios_a_actualizar' => count(array_filter($filas, fn ($f) => ($f['acciones']['predio'] ?? null) === 'actualizar' && $f['errores'] === [])),
            'predios_sin_poligono' => count(array_filter($filas, fn ($f) => ! $f['tiene_poligono'] && $f['clave_catastral'] !== null)),
            'personas_a_crear' => count($personasNuevas),
            'personas_a_reutilizar' => count(array_filter($ventas, fn ($f) => ($f['acciones']['persona'] ?? null) === 'reutilizar' && $f['persona_id'] !== null)),
            'ventas_a_crear' => count($ventas),
            'letras_a_crear' => array_sum(array_map(fn ($f) => $f['letras_a_generar'] ?? 0, $ventas)),
            'superficies_discrepantes' => count(array_filter(
                $filas,
                fn ($f) => $f['m2_excel'] !== null && $f['m2_geojson'] !== null
                    && abs($f['m2_excel'] - $f['m2_geojson']) > self::TOLERANCIA_SUPERFICIE
            )),
            'importe_total' => array_sum(array_column($ventas, 'cantidad_total')),
            'importe_pagado' => array_sum(array_column($ventas, 'cantidad_pagada')),
            'importe_saldo' => array_sum(array_column($ventas, 'saldo')),
            'por_estatus' => $porEstatus,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @return array<int, string>
     */
    private function avisosGlobales(array $filas): array
    {
        $avisos = [];

        $sinPoligono = array_values(array_filter(
            $filas,
            fn ($f) => ! $f['tiene_poligono'] && $f['clave_catastral'] !== null
        ));

        if ($sinPoligono !== []) {
            $avisos[] = sprintf(
                '%d predio(s) sin polígono en el catastro (%s): se crean sin geometría y no se verán en el mapa.',
                count($sinPoligono),
                implode(', ', array_column($sinPoligono, 'clave_catastral_excel'))
            );
        }

        $discrepantes = count(array_filter(
            $filas,
            fn ($f) => $f['m2_excel'] !== null && $f['m2_geojson'] !== null
                && abs($f['m2_excel'] - $f['m2_geojson']) > self::TOLERANCIA_SUPERFICIE
        ));

        if ($discrepantes > 0) {
            $avisos[] = "{$discrepantes} predio(s) con superficie distinta entre el Excel y el catastro: revísalos.";
        }

        return $avisos;
    }

    // --------------------------------------------------------------- aplicación

    /**
     * Escribe el padrón. Vuelve a analizar el archivo para no confiar en un reporte
     * viejo, y aborta si aparecieron bloqueos desde la previsualización.
     *
     * @param  array<string, mixed>  $opciones
     */
    public function aplicar(
        string $xlsxPath,
        string $archivoNombre,
        array $opciones,
        ?int $userId = null,
        ?ImportBatch $batch = null
    ): ImportBatch {
        if ($batch !== null && $batch->estado !== ImportBatch::ESTADO_PREVISUALIZADO) {
            throw new RuntimeException(
                "El lote {$batch->uuid} ya está en estado \"{$batch->estado}\": no se puede volver a aplicar."
            );
        }

        $analisis = $this->analizar($xlsxPath, $opciones);

        if ($analisis['bloqueos'] !== []) {
            throw new RuntimeException(
                'No se puede aplicar la importación: '.implode(' ', $analisis['bloqueos'])
            );
        }

        $opciones = $analisis['opciones'];
        $this->bitacora = [];

        $batch = $this->sinTelescope(fn () => DB::transaction(function () use ($analisis, $opciones, $xlsxPath, $archivoNombre, $userId, $batch) {
            $atributos = [
                'tipo' => 'padron',
                'estado' => ImportBatch::ESTADO_APLICADO,
                'archivo_nombre' => $archivoNombre,
                'archivo_hash' => hash_file('sha256', $xlsxPath),
                'zona_nombre' => $analisis['zona']['nombre'],
                'user_id' => $userId,
                'opciones' => $opciones,
                'aplicado_at' => now(),
            ];

            if ($batch !== null) {
                $batch->update($atributos);
            } else {
                $batch = ImportBatch::create($atributos);
            }

            $zona = $this->obtenerZona($analisis['zona'], $batch);
            $batch->zona_id = $zona->id;
            $batch->save();

            $personasPorLlave = [];
            $creados = ['predios' => 0, 'personas' => 0, 'ventas' => 0, 'letras' => 0, 'telefonos' => 0];

            $escribirFilas = function () use ($analisis, $zona, $opciones, $batch, &$personasPorLlave, &$creados) {
                foreach ($analisis['filas'] as $fila) {
                    $predio = $this->guardarPredio($fila, $zona, $opciones, $batch);
                    $creados['predios']++;

                    if ($fila['solo_predio']) {
                        continue;
                    }

                    $persona = $this->obtenerPersona($fila, $opciones, $batch, $personasPorLlave, $creados);
                    $venta = $this->crearVenta($fila, $persona, $predio, $opciones, $batch);
                    $creados['ventas']++;
                    $creados['letras'] += $this->crearLetras($venta, $fila, $batch);
                }
            };

            // Sin `generar_documentos`, se silencian los eventos de modelo para que
            // VentaObserver no dispare la generación de PDFs por cada venta importada.
            // El lote y su uuid ya están creados arriba, fuera de este alcance.
            $opciones['generar_documentos'] ? $escribirFilas() : Venta::withoutEvents($escribirFilas);

            $this->vaciarBitacora();

            $batch->resumen = array_merge($analisis['resumen'], [
                'creados' => $creados,
                'avisos' => $analisis['avisos'],
                'filas_con_aviso' => $analisis['resumen']['filas_con_aviso'],
            ]);
            $batch->save();

            return $batch->fresh(['registros']);
        }));

        // VentaObserver corre `afterCommit`, así que los PDFs existen apenas cierra la
        // transacción. Se anotan en la bitácora para que la reversión también los limpie.
        if ($opciones['generar_documentos']) {
            $this->registrarDocumentosGenerados($batch);
        }

        return $batch;
    }

    private function registrarDocumentosGenerados(ImportBatch $batch): void
    {
        $filaPorVenta = $batch->registros()
            ->where('model_type', Venta::class)
            ->where('accion', ImportBatchRecord::ACCION_CREADO)
            ->pluck('fila', 'model_id');

        if ($filaPorVenta->isEmpty()) {
            return;
        }

        File::where('fileable_type', Venta::class)
            ->whereIn('fileable_id', $filaPorVenta->keys())
            ->chunkById(500, function ($archivos) use ($batch, $filaPorVenta) {
                foreach ($archivos as $archivo) {
                    $this->registrar(
                        $batch,
                        $archivo,
                        ImportBatchRecord::ACCION_CREADO,
                        $filaPorVenta[$archivo->fileable_id] ?? null,
                        trim(($archivo->tipo ?? 'documento').' · '.$archivo->path)
                    );
                }
            });

        $this->vaciarBitacora();
    }

    /**
     * @param  array<string, mixed>  $datosZona
     */
    private function obtenerZona(array $datosZona, ImportBatch $batch): Zone
    {
        if ($datosZona['id'] !== null) {
            $zona = Zone::findOrFail($datosZona['id']);
            $this->registrar($batch, $zona, ImportBatchRecord::ACCION_REUTILIZADO, null, $zona->nombre);

            return $zona;
        }

        $zona = Zone::create([
            'nombre' => $datosZona['nombre'],
            'dueno_nombre' => $datosZona['dueno_nombre'],
        ]);

        $this->registrar($batch, $zona, ImportBatchRecord::ACCION_CREADO, null, $zona->nombre);

        return $zona;
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  array<string, mixed>  $opciones
     */
    private function guardarPredio(array $fila, Zone $zona, array $opciones, ImportBatch $batch): Predio
    {
        $props = $fila['feature']['properties'] ?? [];
        $poligono = isset($fila['feature']['geometry'])
            ? $this->poligonoDesdeGeometria($fila['feature']['geometry'])
            : null;

        $superficie = $opciones['superficie_desde_excel']
            ? ($fila['m2_excel'] ?? $fila['m2_geojson'])
            : ($fila['m2_geojson'] ?? $fila['m2_excel']);

        $atributos = [
            'zona_id' => $zona->id,
            'lote' => $fila['lote'],
            'manzana' => $fila['manzana'] ?? ($props['manzana'] ?? null),
            'sup_terr' => $superficie,
            'estado' => $this->estadoPredio($fila),
            'gid' => $props['gid'] ?? null,
            'condicion' => $props['condicion'] ?? null,
            'tipo_predio' => $props['tipo_predi'] ?? null,
            'activo' => $props['activo'] ?? null,
            'propietario' => $props['propietari'] ?? null,
            'ubicacion' => $props['ubicacion'] ?? null,
            'sup_cons' => $props['sup_cons'] ?? null,
            'vc' => $props['vc'] ?? null,
            'vt' => $props['vt'] ?? null,
            'tasa' => $props['tasa'] ?? null,
            'area' => $props['area'] ?? null,
        ];

        if ($poligono !== null) {
            $atributos['polygon'] = $poligono;
        }

        if ($fila['predio_id'] !== null) {
            $predio = Predio::findOrFail($fila['predio_id']);
            $previos = array_intersect_key($predio->getOriginal(), $atributos);
            $predio->fill($atributos)->save();

            $this->registrar(
                $batch,
                $predio,
                ImportBatchRecord::ACCION_ACTUALIZADO,
                $fila['fila'],
                $fila['clave_catastral_excel'],
                $previos
            );

            return $predio;
        }

        $predio = Predio::create($atributos + ['clave_catastral' => $fila['clave_catastral']]);
        $this->registrar($batch, $predio, ImportBatchRecord::ACCION_CREADO, $fila['fila'], $fila['clave_catastral_excel']);

        $observacion = $predio->observaciones()->create([
            'observacion' => sprintf(
                'Predio creado por importación de padrón "%s" (lote %s, manzana %s) el %s.',
                $batch->archivo_nombre,
                $fila['lote'] ?? 's/n',
                $fila['manzana'] ?? 's/n',
                now()->format('d/m/Y H:i')
            ),
        ]);

        $this->registrar($batch, $observacion, ImportBatchRecord::ACCION_CREADO, $fila['fila'], $fila['clave_catastral_excel']);

        return $predio;
    }

    /**
     * @param  array<string, mixed>  $fila
     */
    private function estadoPredio(array $fila): string
    {
        if ($fila['solo_predio']) {
            return Predio::ESTADO_DISPONIBLE;
        }

        return $fila['saldo'] > self::TOLERANCIA_IMPORTE
            ? Predio::ESTADO_PAGANDO
            : Predio::ESTADO_PAGADO;
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  array<string, mixed>  $opciones
     * @param  array<string, Person>  $cache
     * @param  array<string, int>  $creados
     */
    private function obtenerPersona(
        array $fila,
        array $opciones,
        ImportBatch $batch,
        array &$cache,
        array &$creados
    ): Person {
        $partes = $fila['nombre_partido'];
        $llave = mb_strtoupper(implode('|', $partes), 'UTF-8');

        if (isset($cache[$llave])) {
            return $cache[$llave];
        }

        if ($fila['persona_id'] !== null) {
            $persona = Person::findOrFail($fila['persona_id']);
            $this->registrar($batch, $persona, ImportBatchRecord::ACCION_REUTILIZADO, $fila['fila'], $fila['comprador']);

            return $cache[$llave] = $persona;
        }

        $persona = Person::create($partes);
        $creados['personas']++;
        $this->registrar($batch, $persona, ImportBatchRecord::ACCION_CREADO, $fila['fila'], $fila['comprador']);

        if ($opciones['importar_telefonos'] && $fila['telefono'] !== null) {
            $telefono = $persona->phones()->create([
                'number' => $fila['telefono'],
                'type' => 'celular',
            ]);
            $creados['telefonos']++;
            $this->registrar($batch, $telefono, ImportBatchRecord::ACCION_CREADO, $fila['fila'], $fila['telefono']);
        }

        return $cache[$llave] = $persona;
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  array<string, mixed>  $opciones
     */
    private function crearVenta(array $fila, Person $persona, Predio $predio, array $opciones, ImportBatch $batch): Venta
    {
        $contratacion = CarbonImmutable::parse($fila['fecha_contratacion']);

        $venta = Venta::create([
            'folio' => $fila['folio'],
            'person_id' => $persona->id,
            'predio_id' => $predio->id,
            'user_id' => $opciones['user_id'],
            'metodo_pago' => $fila['es_contado'] ? 'contado' : 'meses',
            'costo_lote' => $fila['cantidad_total'],
            'enganche' => $fila['anticipo'],
            'meses_a_pagar' => $fila['es_contado'] ? null : $fila['mensualidades'],
            'fecha_primer_abono' => $fila['es_contado'] ? null : $contratacion->addMonth()->toDateString(),
            'saldo_venta' => $fila['saldo'],
            'estado' => $fila['estado_financiero'],
            'estatus_legal' => $fila['estatus_legal'],
            'created_at' => $contratacion,
            'updated_at' => $contratacion,
        ]);

        $this->registrar($batch, $venta, ImportBatchRecord::ACCION_CREADO, $fila['fila'], $venta->folio);

        return $venta;
    }

    /**
     * Genera la letra de anticipo más una por mensualidad. Las primeras `L. PA` quedan
     * pagadas; el resto pendientes. No se generan pagos ni tickets: el Excel no trae
     * las fechas reales de pago e inventarlas ensuciaría el historial de caja.
     *
     * Las letras se insertan de golpe, no una por una: contra una base remota cada
     * INSERT cuesta un viaje de red completo, y un padrón de 162 ventas son ~4,400
     * letras. En bloque, cada venta cuesta una escritura en lugar de treinta.
     *
     * @param  array<string, mixed>  $fila
     */
    private function crearLetras(Venta $venta, array $fila, ImportBatch $batch): int
    {
        $contratacion = CarbonImmutable::parse($fila['fecha_contratacion']);
        $ahora = now();

        $base = [
            'venta_id' => $venta->id,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];

        // Contado: una sola letra por el costo completo, con el tipo que los reportes
        // ya reconocen (ReportService suma los abonos de las letras tipo «contado»).
        if ($fila['es_contado']) {
            $renglones = [$base + [
                'descripcion' => 'Contado',
                'monto' => $fila['cantidad_total'],
                'saldo' => 0,
                'consecutivo' => 0,
                'tipo' => 'contado',
                'estado' => 'pagado',
                'fecha_vencimiento' => $contratacion->toDateString(),
            ]];
        } else {
            // El anticipo se considera cubierto salvo que lo pagado no alcance a cubrirlo.
            $saldoAnticipo = round(max(0.0, $fila['anticipo'] - min($fila['cantidad_pagada'], $fila['anticipo'])), 2);

            $renglones = [$base + [
                'descripcion' => 'Anticipo',
                'monto' => $fila['anticipo'],
                'saldo' => $saldoAnticipo,
                'consecutivo' => 0,
                'tipo' => 'anticipo',
                'estado' => $saldoAnticipo <= self::TOLERANCIA_IMPORTE ? 'pagado' : 'pendiente',
                'fecha_vencimiento' => $contratacion->toDateString(),
            ]];

            $montos = $this->montosMensualidades(
                $fila['cantidad_total'],
                $fila['anticipo'],
                $fila['pagare'],
                $fila['mensualidades']
            );

            foreach ($montos as $i => $monto) {
                $pagada = $i < $fila['letras_pagadas'];

                $renglones[] = $base + [
                    'descripcion' => 'Letra '.($i + 1),
                    'monto' => $monto,
                    'saldo' => $pagada ? 0 : $monto,
                    'consecutivo' => $i + 1,
                    'tipo' => 'letra',
                    'estado' => $pagada ? 'pagado' : 'pendiente',
                    'fecha_vencimiento' => $contratacion->addMonths($i + 1)->toDateString(),
                ];
            }
        }

        Letra::insert($renglones);

        // insert() no devuelve ids, así que se releen para la bitácora: sigue siendo
        // una consulta por venta en lugar de una por letra.
        $letras = $venta->letras()->orderBy('consecutivo')->orderBy('id')->get(['id', 'descripcion']);

        foreach ($letras as $letra) {
            $this->registrar(
                $batch,
                $letra,
                ImportBatchRecord::ACCION_CREADO,
                $fila['fila'],
                $venta->folio.' / '.$letra->descripcion
            );
        }

        $venta->calcularCache();

        return count($renglones);
    }

    // ----------------------------------------------------------------- rollback

    /**
     * Deshace un lote aplicado: borra en orden inverso todo lo que creó y deja
     * intacto lo que sólo reutilizó.
     *
     * @return array<string, int> modelo => registros borrados
     */
    public function revertir(ImportBatch $batch, ?int $userId = null): array
    {
        if (! $batch->puedeRevertirse()) {
            throw new RuntimeException(
                "El lote {$batch->uuid} está en estado \"{$batch->estado}\" y no se puede revertir."
            );
        }

        $creados = $batch->registros()
            ->where('accion', ImportBatchRecord::ACCION_CREADO)
            ->get()
            ->groupBy('model_type')
            ->map(fn ($registros) => $registros->pluck('model_id')->all());

        $this->verificarDependenciasExternas($creados, $batch);

        $this->borrarArchivosDeDisco($creados[File::class] ?? []);

        return DB::transaction(function () use ($batch, $creados, $userId) {
            $borrados = [];

            foreach (self::ORDEN_ROLLBACK as $clase) {
                $ids = $creados[$clase] ?? [];

                if ($ids === []) {
                    continue;
                }

                try {
                    // withTrashed: si algo se canceló después de importarse, igual
                    // hay que borrarlo para dejar la base como estaba.
                    $consulta = in_array(SoftDeletes::class, class_uses_recursive($clase), true)
                        ? $clase::withTrashed()
                        : $clase::query();

                    $borrados[class_basename($clase)] = $consulta->whereIn('id', $ids)->forceDelete();
                } catch (Throwable $e) {
                    throw new RuntimeException(
                        'No se pudo borrar '.class_basename($clase).': hay registros que dependen de estos datos. '
                            .'Elimínalos primero. Detalle: '.$e->getMessage(),
                        previous: $e
                    );
                }
            }

            $this->restaurarActualizados($batch);

            $batch->update([
                'estado' => ImportBatch::ESTADO_REVERTIDO,
                'revertido_at' => now(),
                'revertido_por' => $userId,
            ]);

            return $borrados;
        });
    }

    /**
     * Si alguien registró ventas o pagos sobre lo importado, revertir rompería su
     * información: mejor abortar con un mensaje claro que fallar por una llave foránea.
     *
     * @param  \Illuminate\Support\Collection<string, array<int, int>>  $creados
     */
    private function verificarDependenciasExternas($creados, ImportBatch $batch): void
    {
        $ventasDelLote = $creados[Venta::class] ?? [];
        $prediosDelLote = $creados[Predio::class] ?? [];
        $personasDelLote = $creados[Person::class] ?? [];

        $problemas = [];

        if ($prediosDelLote !== []) {
            $ajenas = Venta::withTrashed()
                ->whereIn('predio_id', $prediosDelLote)
                ->when($ventasDelLote !== [], fn ($q) => $q->whereNotIn('id', $ventasDelLote))
                ->count();

            if ($ajenas > 0) {
                $problemas[] = "{$ajenas} venta(s) creadas fuera de esta importación usan predios del lote";
            }
        }

        if ($personasDelLote !== []) {
            $ajenas = Venta::withTrashed()
                ->where(fn ($q) => $q->whereIn('person_id', $personasDelLote)->orWhereIn('aval_id', $personasDelLote))
                ->when($ventasDelLote !== [], fn ($q) => $q->whereNotIn('id', $ventasDelLote))
                ->count();

            if ($ajenas > 0) {
                $problemas[] = "{$ajenas} venta(s) creadas fuera de esta importación usan personas del lote";
            }
        }

        if ($ventasDelLote !== []) {
            $pagos = DB::table('abonos')
                ->join('letras', 'letras.id', '=', 'abonos.letra_id')
                ->whereIn('letras.venta_id', $ventasDelLote)
                ->count();

            if ($pagos > 0) {
                $problemas[] = "{$pagos} abono(s) registrados sobre las letras importadas";
            }
        }

        if ($problemas !== []) {
            throw new RuntimeException(
                "No se puede revertir el lote {$batch->uuid} sin perder información posterior: "
                    .implode('; ', $problemas).'.'
            );
        }
    }

    /**
     * Los PDFs viven en disco, no en la base: borrar el renglón de `files` no los quita.
     *
     * @param  array<int, int>  $ids
     */
    private function borrarArchivosDeDisco(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        File::whereIn('id', $ids)->chunkById(500, function ($archivos) {
            foreach ($archivos as $archivo) {
                if ($archivo->path === null) {
                    continue;
                }

                // Un archivo que ya no está no debe frenar la reversión.
                rescue(fn () => Storage::disk($archivo->disk ?? 'public')->delete($archivo->path), report: false);
            }
        });
    }

    private function restaurarActualizados(ImportBatch $batch): void
    {
        $actualizados = $batch->registros()
            ->where('accion', ImportBatchRecord::ACCION_ACTUALIZADO)
            ->whereNotNull('datos_previos')
            ->get();

        foreach ($actualizados as $registro) {
            $clase = $registro->model_type;
            $modelo = $clase::find($registro->model_id);

            if ($modelo !== null) {
                $modelo->forceFill($registro->datos_previos)->save();
            }
        }
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Montos de las mensualidades, ya redondeados a centavos.
     *
     * El pagaré del Excel suele traer decimales periódicos (1233.3333…), así que se
     * redondea a centavos y la última letra absorbe la diferencia contra el monto ya
     * redondeado: así la suma de las letras da exactamente el monto a financiar.
     * Lo usan tanto el análisis como la escritura, para que no puedan discrepar.
     *
     * @return array<int, float>
     */
    public function montosMensualidades(
        float $cantidadTotal,
        float $anticipo,
        float $pagare,
        int $mensualidades
    ): array {
        if ($mensualidades < 1) {
            return [];
        }

        $financiar = round($cantidadTotal - $anticipo, 2);
        $regular = round($pagare, 2);

        $montos = array_fill(0, $mensualidades - 1, $regular);
        $montos[] = round($financiar - $regular * ($mensualidades - 1), 2);

        return $montos;
    }

    /**
     * @param  array<string, mixed>|null  $datosPrevios
     */
    private function registrar(
        ImportBatch $batch,
        object $modelo,
        string $accion,
        ?int $fila,
        ?string $referencia,
        ?array $datosPrevios = null
    ): void {
        $ahora = now();

        // Se acumula y se inserta por bloques: una importación normal deja ~5 mil
        // renglones de bitácora y hacerlos uno por uno domina el tiempo total.
        $this->bitacora[] = [
            'import_batch_id' => $batch->id,
            'fila' => $fila,
            'model_type' => $modelo::class,
            'model_id' => $modelo->getKey(),
            'accion' => $accion,
            'referencia' => $referencia !== null ? mb_substr($referencia, 0, 255) : null,
            'datos_previos' => $datosPrevios !== null ? json_encode($datosPrevios, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];

        if (count($this->bitacora) >= 500) {
            $this->vaciarBitacora();
        }
    }

    private function vaciarBitacora(): void
    {
        if ($this->bitacora === []) {
            return;
        }

        ImportBatchRecord::insert($this->bitacora);
        $this->bitacora = [];
    }

    /**
     * Telescope registra cada query y cada evento de modelo. En una importación de
     * decenas de miles de escrituras eso multiplica el tiempo y llena la base de
     * entradas que a nadie le sirven, así que se apaga mientras dura.
     */
    private function sinTelescope(callable $callback): mixed
    {
        if (! class_exists(Telescope::class)) {
            return $callback();
        }

        return Telescope::withoutRecording($callback);
    }

    /**
     * @param  array<string, mixed>  $geometria
     */
    private function poligonoDesdeGeometria(array $geometria): ?Polygon
    {
        $tipo = $geometria['type'] ?? null;

        $anillo = match ($tipo) {
            'MultiPolygon' => $geometria['coordinates'][0][0] ?? null,
            'Polygon' => $geometria['coordinates'][0] ?? null,
            default => null,
        };

        if (! is_array($anillo) || count($anillo) < 3) {
            return null;
        }

        // GeoJSON viene en [lng, lat]; Point espera (lat, lng).
        // El SRID va explícito: `predios.polygon` es SRID 4326 y el paquete
        // construye en SRID 0 por defecto, lo que MySQL rechaza.
        $puntos = array_map(
            fn (array $c) => new Point((float) $c[1], (float) $c[0], Srid::WGS84),
            $anillo
        );

        if ($puntos[0]->latitude !== end($puntos)->latitude || $puntos[0]->longitude !== end($puntos)->longitude) {
            $puntos[] = $puntos[0];
        }

        return new Polygon([new LineString($puntos, Srid::WGS84)], Srid::WGS84);
    }

    /**
     * Los nombres del padrón traen anotaciones del capturista: "Y COP" (y copropietario)
     * y paréntesis con un segundo titular. Se guardan como aviso en lugar de meterlos
     * al nombre de la persona.
     *
     * @return array{0: ?string, 1: array<int, string>}
     */
    public function limpiarNombre(?string $crudo): array
    {
        if ($crudo === null || trim($crudo) === '') {
            return [null, []];
        }

        $avisos = [];
        $nombre = trim(preg_replace('/\s+/u', ' ', $crudo) ?? '');

        if (preg_match('/\(([^)]*)\)/u', $nombre, $m)) {
            $avisos[] = 'El nombre traía "'.trim($m[1]).'" entre paréntesis: revísalo, puede ser un copropietario.';
            $nombre = trim(preg_replace('/\s*\([^)]*\)\s*/u', ' ', $nombre) ?? '');
        }

        if (preg_match('/\s+Y\s+COP\.?$/iu', $nombre)) {
            $avisos[] = 'El nombre terminaba en "Y COP" (y copropietario): se importa sólo el titular.';
            $nombre = trim(preg_replace('/\s+Y\s+COP\.?$/iu', '', $nombre) ?? '');
        }

        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? '');

        return [$nombre === '' ? null : $nombre, $avisos];
    }

    /**
     * Mismo criterio que MigradorService: los dos últimos tokens son los apellidos.
     *
     * @return array{nombres: string, apellido_paterno: string, apellido_materno: string}
     */
    public function separarNombre(string $nombre): array
    {
        $partes = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return match (count($partes)) {
            0 => ['nombres' => '', 'apellido_paterno' => '', 'apellido_materno' => ''],
            1 => ['nombres' => $partes[0], 'apellido_paterno' => '', 'apellido_materno' => ''],
            2 => ['nombres' => $partes[0], 'apellido_paterno' => $partes[1], 'apellido_materno' => ''],
            default => [
                'apellido_materno' => array_pop($partes),
                'apellido_paterno' => array_pop($partes),
                'nombres' => implode(' ', $partes),
            ],
        };
    }

    /** Filas que describen áreas comunes o ventas canceladas: entran como predio, sin venta. */
    private function esFilaSinVenta(?string $comprador): bool
    {
        if ($comprador === null) {
            return true;
        }

        return (bool) preg_match('/\b(AREA|ÁREA)\b|DONACI|VIALIDAD|CANCELAD/iu', $comprador);
    }

    private function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim(preg_replace('/\s+/u', ' ', (string) $valor) ?? '');

        return $texto === '' ? null : $texto;
    }

    private function numero(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $limpio = preg_replace('/[^\d.\-]/', '', (string) $valor) ?? '';

        return is_numeric($limpio) ? (float) $limpio : null;
    }

    private function fecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        // Serial de Excel: días desde 1899-12-30. Sólo si el lector no lo convirtió ya.
        if (is_numeric($valor)) {
            $serial = (float) $valor;

            if ($serial < 1 || $serial > 100000) {
                return null;
            }

            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $serial)->toDateString();
        }

        try {
            return CarbonImmutable::parse((string) $valor)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
