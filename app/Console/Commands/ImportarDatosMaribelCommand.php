<?php

namespace App\Console\Commands;

use App\Services\Api\MigradorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportarDatosMaribelCommand extends Command
{
    protected $signature = 'importar:datos-maribel
                            {--zona-nombre=Maribel : Nombre de la zona en Zone}
                            {--dueno-nombre=dueno_maribel : Identificador dueno_nombre de la zona}';

    protected $description = 'Importa registros desde database/seeders/bd_maribel.csv usando MigradorService';

    public function handle(MigradorService $migrador): int
    {
        $path = database_path('seeders/bd_maribel.csv');

        if (! is_readable($path)) {
            $this->error("No se puede leer el archivo: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->error("No se pudo abrir el archivo: {$path}");

            return self::FAILURE;
        }

        $datos = [];
        while (($cols = fgetcsv($handle)) !== false) {
            $row = $this->mapearFilaCsv($cols);
            if ($row !== null) {
                $datos[] = $row;
            }
        }
        fclose($handle);

        if ($datos === []) {
            $this->warn('No se encontraron filas válidas en el CSV.');

            return self::FAILURE;
        }

        $zona = [
            'nombre' => (string) $this->option('zona-nombre'),
            'dueno_nombre' => (string) $this->option('dueno-nombre'),
        ];

        $this->info('Filas a importar: ' . count($datos));

        $migrador->iniciar($datos, $zona);

        $this->info('Importación finalizada.');

        return self::SUCCESS;
    }

    /**
     * CSV: 0 fecha_contratacion, 1 comprador, 2 lote, 3 manzana, 4 anticipo, 5 pagare, 6 Letras pagadas.
     *
     * @param  array<int, string|null>  $cols
     * @return array<string, mixed>|null
     */
    private function mapearFilaCsv(array $cols): ?array
    {
        if (count($cols) < 7) {
            return null;
        }

        $comprador = trim((string) ($cols[1] ?? ''));
        if ($comprador === '') {
            return null;
        }

        $fechaRaw = trim((string) ($cols[0] ?? ''));
        $fechaContratacion = $this->parseFechaContratacion($fechaRaw);

        $lote = $this->parseEntero($cols[2] ?? null);
        $manzana = $this->parseEntero($cols[3] ?? null);
        if ($lote === null || $manzana === null) {
            return null;
        }

        return [
            'contrato' => null,
            'L' => $lote,
            'manzana' => $manzana,
            'fecha_contratacion' => $fechaContratacion,
            'comprador' => $comprador,
            'telefono' => null,
            'm2' => null,
            'Letras pagadas' => $this->parseLetrasPagadas($cols[6] ?? null),
            'cantidad_total' => 300000,
            'anticipo' => $this->parseDecimal($cols[4] ?? null),
            'letras' => null,
            'pagare' => $this->parseDecimal($cols[5] ?? null),
            'saldo' => null,
            'cantidad_pagada' => null,
        ];
    }

    private function parseFechaContratacion(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        // eliminar BOM y espacios invisibles
        $raw = trim($raw);
        $raw = preg_replace('/^\x{FEFF}/u', '', $raw);

        try {
            return Carbon::createFromFormat('d/m/Y', $raw)->startOfDay()->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseEntero(mixed $value): ?int
    {
        $n = $this->parseDecimal($value);
        if ($n === null) {
            return null;
        }

        return (int) $n;
    }

    /**
     * Acepta valores con comas de miles y espacios (ej. " 3,500.00 ").
     */
    private function parseDecimal(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $s = trim((string) $value);
        if ($s === '' || strcasecmp($s, '-') === 0) {
            return null;
        }

        $s = str_replace(["\u{00A0}", ' '], '', $s);
        $s = str_replace(',', '', $s);

        if (! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    private function parseLetrasPagadas(mixed $value): ?int
    {
        $n = $this->parseDecimal($value);
        if ($n === null) {
            return null;
        }

        return (int) $n;
    }
}
