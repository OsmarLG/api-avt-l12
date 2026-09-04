<?php

namespace App\Services\Imports;

use RuntimeException;

/**
 * Busca features por clave catastral dentro del GeoJSON del catastro.
 *
 * El archivo pesa ~150 MB con ~179k features, así que se recorre en streaming:
 * el GeoJSON que exporta ogr2ogr escribe un feature por línea, de modo que se lee
 * línea por línea y sólo se decodifica el JSON de las que traen una clave buscada
 * (el filtro previo es una regex, mucho más barata que json_decode). Si un feature
 * llegara partido en varias líneas, se acumula hasta poder decodificarlo.
 */
class GeoJsonFeatureFinder
{
    /** Un feature suelto nunca se acerca a esto; sirve para no crecer sin límite ante un archivo inesperado. */
    private const LIMITE_BUFFER = 67108864; // 64 MB

    public function __construct(private readonly string $path) {}

    public function path(): string
    {
        return $this->path;
    }

    public function existe(): bool
    {
        return is_readable($this->path);
    }

    /**
     * El Excel escribe la clave con guiones (103-012-124-001) y el GeoJSON sin ellos
     * (103012124001). Se comparan por sus 12 dígitos; cualquier otra cosa
     * ("AREA COMUN 3", claves truncadas) se descarta: quitarle los no-dígitos a
     * "AREA COMUN 3" daría "3" y colisionaría con decenas de features.
     */
    public static function normalizar(mixed $clave): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) ($clave ?? '')) ?? '';

        return strlen($digitos) === 12 ? $digitos : null;
    }

    /**
     * @param  array<int, string>  $clavesNormalizadas
     * @return array<string, array<string, mixed>> clave normalizada => feature
     */
    public function buscar(array $clavesNormalizadas): array
    {
        if ($clavesNormalizadas === []) {
            return [];
        }

        if (! $this->existe()) {
            throw new RuntimeException("No se encontró el GeoJSON en: {$this->path}");
        }

        $buscadas = array_flip(array_unique($clavesNormalizadas));
        $pendientes = count($buscadas);
        $encontradas = [];

        $handle = fopen($this->path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("No se pudo abrir el GeoJSON: {$this->path}");
        }

        $buffer = '';
        $dentroDeFeatures = false;

        try {
            while (($linea = fgets($handle)) !== false) {
                // La cabecera del FeatureCollection ("type", "name", "crs") también trae
                // llaves, así que no se empieza a acumular hasta abrir el arreglo de features.
                if (! $dentroDeFeatures) {
                    $posicion = strpos($linea, '"features"');

                    if ($posicion === false) {
                        continue;
                    }

                    $dentroDeFeatures = true;
                    $corchete = strpos($linea, '[', $posicion);

                    if ($corchete === false) {
                        continue;
                    }

                    $linea = substr($linea, $corchete + 1);

                    if (trim($linea) === '') {
                        continue;
                    }
                }

                $buffer .= $linea;

                if (strlen($buffer) > self::LIMITE_BUFFER) {
                    throw new RuntimeException(
                        'El GeoJSON no tiene el formato esperado (un feature por línea): '
                            .'se acumularon más de 64 MB sin cerrar un objeto.'
                    );
                }

                $candidato = rtrim(trim($buffer), ',');

                // Cierre del arreglo y del documento: no son features.
                if ($candidato === '' || $candidato[0] !== '{') {
                    $buffer = '';

                    continue;
                }

                // Mientras no cierren todas las llaves, el feature viene partido en varias líneas.
                if (! self::objetoCompleto($candidato)) {
                    continue;
                }

                $buffer = '';

                // Filtro barato: la inmensa mayoría de los ~179k features no nos interesan.
                if (! preg_match('/"clavecatas"\s*:\s*"([^"]*)"/', $candidato, $m)) {
                    continue;
                }

                $clave = self::normalizar($m[1]);

                if ($clave === null || ! isset($buscadas[$clave]) || isset($encontradas[$clave])) {
                    continue;
                }

                $feature = json_decode($candidato, true);

                if (! is_array($feature) || ! isset($feature['properties'])) {
                    continue;
                }

                $encontradas[$clave] = $feature;
                $pendientes--;

                if ($pendientes <= 0) {
                    return $encontradas;
                }
            }
        } finally {
            fclose($handle);
        }

        return $encontradas;
    }

    /**
     * ¿El fragmento cierra todas sus llaves fuera de cadenas?
     *
     * Contar llaves a secas no sirve: el catastro real trae direcciones como
     * "PEDRO CADENA ESQ ARROYO DE LOS POTRILLOS }", y una llave suelta dentro de un
     * string dejaría el buffer creciendo para siempre. Se recorre saltando con
     * strcspn de un carácter estructural al siguiente, así los kilobytes de
     * coordenadas —que no traen ninguno— no cuestan nada.
     */
    private static function objetoCompleto(string $json): bool
    {
        $largo = strlen($json);
        $i = 0;
        $profundidad = 0;
        $enCadena = false;

        while ($i < $largo) {
            if ($enCadena) {
                $i += strcspn($json, '"'.chr(92), $i);

                if ($i >= $largo) {
                    break;
                }

                if ($json[$i] === chr(92)) { // backslash: se salta el carácter escapado
                    $i += 2;

                    continue;
                }

                $enCadena = false;
                $i++;

                continue;
            }

            $i += strcspn($json, '{}"', $i);

            if ($i >= $largo) {
                break;
            }

            match ($json[$i]) {
                '"' => $enCadena = true,
                '{' => $profundidad++,
                default => $profundidad--,
            };

            $i++;
        }

        return ! $enCadena && $profundidad === 0;
    }

    /**
     * Superficie del terreno según el catastro, para contrastarla con los M2 del Excel.
     *
     * @param  array<string, mixed>  $feature
     */
    public static function superficie(array $feature): ?float
    {
        $props = $feature['properties'] ?? [];
        $valor = $props['sup_terr'] ?? $props['area'] ?? null;

        return is_numeric($valor) ? (float) $valor : null;
    }
}
