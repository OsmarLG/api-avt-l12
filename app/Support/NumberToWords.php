<?php

namespace App\Support;

class NumberToWords
{
    private static $unidades = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
    private static $decenas = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
    private static $dieces = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    private static $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

    public static function convert($number): string
    {
        if ($number == 0)
            return 'cero';
        if ($number == 100)
            return 'cien';

        $parts = explode('.', number_format($number, 2, '.', ''));
        $entero = (int) $parts[0];
        $decimal = $parts[1];

        $words = self::convertGroup($entero);

        return strtoupper($words) . " PESOS " . $decimal . "/100 M.N.";
    }

    public static function convertToSpanishWords($number): string
    {
        return self::convertGroup((int) $number);
    }

    private static function convertGroup($n): string
    {
        if ($n < 10)
            return self::$unidades[$n];
        if ($n < 20)
            return self::$decenas[$n - 10];
        if ($n < 100) {
            $u = $n % 10;
            $d = floor($n / 10);
            if ($u == 0)
                return self::$dieces[$d];
            if ($d == 2)
                return 'veinti' . self::$unidades[$u];
            return self::$dieces[$d] . ' y ' . self::$unidades[$u];
        }
        if ($n < 1000) {
            $d = $n % 100;
            $c = floor($n / 100);
            if ($d == 0)
                return self::$centenas[$c];
            return self::$centenas[$c] . ' ' . self::convertGroup($d);
        }
        if ($n < 1000000) {
            $c = $n % 1000;
            $m = floor($n / 1000);
            $words = ($m == 1 ? '' : self::convertGroup($m)) . ' mil';
            if ($c > 0)
                $words .= ' ' . self::convertGroup($c);
            return trim($words);
        }
        return (string) $n; // Simplified for millions
    }
}
