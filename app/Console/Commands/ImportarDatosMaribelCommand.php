<?php

namespace App\Console\Commands;

use App\Models\Letra;
use App\Models\User;
use App\Services\Api\MigradorService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ImportarDatosMaribelCommand extends Command
{
    protected $signature = 'importar:datos-maribel
                            {--zona-nombre=Etapa 1 : Nombre de la zona en Zone}
                            {--dueno-nombre="dueno Etapa 1" : Identificador dueno_nombre de la zona}';

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

        $this->crearUsuarioAdministrador();

        $migrador->iniciar($datos, $zona);

        // $letrasActualizadas = $this->ajustarDiaVencimientoLetrasAl(20);
        //$this->info("Vencimiento de letras ajustado al día 20: {$letrasActualizadas} registro(s).");
        /*

        
            UPDATE letras
            SET fecha_vencimiento = DATE(
                CONCAT(
                    YEAR(fecha_vencimiento),
                    '-',
                    LPAD(MONTH(fecha_vencimiento), 2, '0'),
                    '-20'
                )
            )
            WHERE tipo != 'anticipo'
            AND fecha_vencimiento IS NOT NULL;


            */
        $this->info('Importación finalizada.');

        return self::SUCCESS;
    }

    /**
     * CSV (bd_maribel): 0 fecha_contratacion, 1 ignorar (fecha corte), 2 comprador,
     * 3 manzana, 4 lote, 5 anticipo, 6 pagare, 7 letras pagadas, 8 cantidad_pagada.
     *
     * @param  array<int, string|null>  $cols
     * @return array<string, mixed>|null
     */
    private function mapearFilaCsv(array $cols): ?array
    {
        $comprador = trim((string) ($cols[2] ?? ''));
        if ($comprador === '' || strcasecmp($comprador, 'cliente') === 0) {
            return null;
        }

        $fechaRaw = trim((string) ($cols[0] ?? ''));
        $fechaContratacion = $this->parseFechaContratacion($fechaRaw);

        $manzana = $this->parseEntero($cols[3] ?? null);
        $lote = $this->parseEntero($cols[4] ?? null);
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
            'Letras pagadas' => $this->parseLetrasPagadas($cols[7] ?? null),
            'cantidad_total' => 300000,
            'anticipo' => $this->parseDecimal($cols[5] ?? null),
            'letras' => null,
            'pagare' => $this->parseDecimal($cols[6] ?? null),
            'saldo' => null,
            'cantidad_pagada' => $this->parseDecimal($cols[8] ?? null),
        ];
    }

    /**
     * Mantiene año y mes de fecha_vencimiento; fija el día (anticipos no se modifican).
     */
    private function ajustarDiaVencimientoLetrasAl(int $dia): int
    {
        $dia = max(1, min(28, $dia));

        return Letra::query()
            ->where('tipo', '!=', 'anticipo')
            ->whereNotNull('fecha_vencimiento')
            ->update([
                'fecha_vencimiento' => DB::raw(
                    "DATE(CONCAT(YEAR(fecha_vencimiento), '-', LPAD(MONTH(fecha_vencimiento), 2, '0'), '-', '{$dia}'))"
                ),
            ]);
    }

    private function crearUsuarioAdministrador(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@hotmail.com'],
            [
                'name' => 'administrador',
                'username' => 'administrador',
                'password' => Hash::make('password2026'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );
        $user->syncRoles([$role->name]);

        $this->info("Usuario administrador listo (id: {$user->id}, email: admin@hotmail.com).");
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
