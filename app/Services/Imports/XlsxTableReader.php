<?php

namespace App\Services\Imports;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Lee una hoja de cálculo tabular (primera fila = encabezados) a un arreglo de filas
 * asociativas. Normaliza los encabezados y convierte los seriales de fecha de Excel.
 */
class XlsxTableReader
{
    /**
     * @return array{headers: array<string, string>, rows: array<int, array<string, mixed>>}
     */
    public function read(string $path, ?string $sheetName = null): array
    {
        if (! is_readable($path)) {
            throw new RuntimeException("No se puede leer el archivo: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(false); // necesitamos los formatos para detectar fechas
        $spreadsheet = $reader->load($path);

        $sheet = $sheetName !== null
            ? $spreadsheet->getSheetByName($sheetName)
            : $spreadsheet->getSheet(0);

        if ($sheet === null) {
            throw new RuntimeException("La hoja \"{$sheetName}\" no existe en el archivo.");
        }

        $ultimaColumna = $sheet->getHighestDataColumn();
        $ultimaFila = $sheet->getHighestDataRow();

        // Encabezados: columna => nombre normalizado
        $headers = [];
        foreach ($sheet->getColumnIterator('A', $ultimaColumna) as $column) {
            $letra = $column->getColumnIndex();
            $valor = $sheet->getCell($letra.'1')->getValue();
            $normalizado = $this->normalizarEncabezado($valor);

            if ($normalizado !== '') {
                $headers[$letra] = $normalizado;
            }
        }

        if ($headers === []) {
            throw new RuntimeException('La primera fila del archivo no contiene encabezados.');
        }

        $rows = [];
        for ($fila = 2; $fila <= $ultimaFila; $fila++) {
            $registro = ['_fila' => $fila];
            $vacia = true;

            foreach ($headers as $letra => $nombre) {
                $valor = $this->valorDeCelda($sheet->getCell($letra.$fila));
                $registro[$nombre] = $valor;

                if ($valor !== null && $valor !== '') {
                    $vacia = false;
                }
            }

            if (! $vacia) {
                $rows[] = $registro;
            }
        }

        $spreadsheet->disconnectWorksheets();

        return ['headers' => array_values($headers), 'rows' => $rows];
    }

    /**
     * Los encabezados reales traen espacios de sobra y acentos inconsistentes
     * ("ESTATUS ", "CAT. TOTAL"), así que se comparan siempre normalizados.
     */
    public function normalizarEncabezado(mixed $valor): string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '') {
            return '';
        }

        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = strtr($texto, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);

        return trim(preg_replace('/\s+/u', ' ', $texto) ?? '');
    }

    private function valorDeCelda(Cell $cell): mixed
    {
        $valor = $cell->getValue();

        if ($valor === null) {
            return null;
        }

        if (is_numeric($valor) && ExcelDate::isDateTime($cell)) {
            return ExcelDate::excelToDateTimeObject((float) $valor)->format('Y-m-d');
        }

        if (is_string($valor)) {
            $valor = trim($valor);

            return $valor === '' ? null : $valor;
        }

        return $valor;
    }
}
