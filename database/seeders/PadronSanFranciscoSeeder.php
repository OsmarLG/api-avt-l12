<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Predio;
use App\Models\Venta;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PadronSanFranciscoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $padronSanFrancisco = [
            [
                "contrato" => "SF001",
                "L" => 1,
                "manzana" => 1,
                "fecha_contratacion" => "2022-13-07",
                "comprador" => "Josefina Villalobos Camacho",
                "telefono" => "9121502509",
                "m2" => 1366.98,
                "Letras pagadas" => 21,
                "cantidad_total" => 341745.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 4677.01,
                "saldo" => 238527.71,
                "cantidad_pagada" => 103217.29
            ],
            [
                "contrato" => "SF002",
                "L" => 2,
                "manzana" => 1,
                "fecha_contratacion" => "2022-10-02",
                "comprador" => "Rosario Maricela Mancilla Muñoa",
                "telefono" => "3324228828",
                "m2" => 1004.39,
                "Letras pagadas" => 49,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 65486.11,
                "cantidad_pagada" => 144513.89
            ],
            [
                "contrato" => "SF003",
                "L" => 3,
                "manzana" => 1,
                "fecha_contratacion" => "2022-18-02",
                "comprador" => "Alejandro Carrera Lopez",
                "telefono" => "4627179797",
                "m2" => 1003.83,
                "Letras pagadas" => 72,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => null,
                "cantidad_pagada" => 210000.00
            ],
            [
                "contrato" => "SF004",
                "L" => 4,
                "manzana" => 1,
                "fecha_contratacion" => "2022-18-02",
                "comprador" => "Maria de la Cruz Garcia Flores",
                "telefono" => "6122185309",
                "m2" => 1003.27,
                "Letras pagadas" => 46,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 74027.78,
                "cantidad_pagada" => 135972.22
            ],
            [
                "contrato" => "SF005",
                "L" => 5,
                "manzana" => 1,
                "fecha_contratacion" => "2022-21-02",
                "comprador" => "Maria de la Cruz Garcia Flores",
                "telefono" => "6122185309",
                "m2" => 1002.71,
                "Letras pagadas" => 39,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 93958.33,
                "cantidad_pagada" => 116041.67
            ],
            [
                "contrato" => "SF006",
                "L" => 6,
                "manzana" => 1,
                "fecha_contratacion" => "2022-18-02",
                "comprador" => "Jose Alberto Carballo Cota",
                "telefono" => "6121369301",
                "m2" => 1002.15,
                "Letras pagadas" => 16,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 159444.44,
                "cantidad_pagada" => 50555.56
            ],
            [
                "contrato" => "SF007",
                "L" => 7,
                "manzana" => 1,
                "fecha_contratacion" => "2021-05-07",
                "comprador" => "Claudia Guadalupe Magdon Amarillas",
                "telefono" => "6131292672",
                "m2" => 1001.59,
                "Letras pagadas" => 55,
                "cantidad_total" => 250000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 3402.78,
                "saldo" => 57847.22,
                "cantidad_pagada" => 192152.78
            ],
            [
                "contrato" => "SF008",
                "L" => 8,
                "manzana" => 1,
                "fecha_contratacion" => "2021-16-07",
                "comprador" => "Oscar Vidal Garciglia Bañuelos",
                "telefono" => "6121575343",
                "m2" => 1005.71,
                "Letras pagadas" => 55,
                "cantidad_total" => 250000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 3402.78,
                "saldo" => 57847.22,
                "cantidad_pagada" => 192152.78
            ],
            [
                "contrato" => "SF009",
                "L" => 9,
                "manzana" => 1,
                "fecha_contratacion" => "2021-24-12",
                "comprador" => "Cancelado",
                "telefono" => "6121413286",
                "m2" => 1005.71,
                "Letras pagadas" => 11,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 173680.56,
                "cantidad_pagada" => 36319.44
            ],
            [
                "contrato" => "SF010",
                "L" => 10,
                "manzana" => 1,
                "fecha_contratacion" => "2021-09-08",
                "comprador" => "Nayelly Teresa Sanchez Higuera",
                "telefono" => "9631470313",
                "m2" => 1005.71,
                "Letras pagadas" => 28,
                "cantidad_total" => 190000.00,
                "anticipo" => 50000.00,
                "letras" => 28,
                "pagare" => 5000.00,
                "saldo" => null,
                "cantidad_pagada" => 190000.00
            ],
            [
                "contrato" => "SF011",
                "L" => 11,
                "manzana" => 1,
                "fecha_contratacion" => "2021-04-12",
                "comprador" => "Cancelado",
                "telefono" => "6122265242",
                "m2" => 1005.71,
                "Letras pagadas" => 18,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 153750.00,
                "cantidad_pagada" => 56250.00
            ],
            [
                "contrato" => "SF012",
                "L" => 12,
                "manzana" => 1,
                "fecha_contratacion" => "2022-29-07",
                "comprador" => "Julian Gabriel Geraldo Miranda",
                "telefono" => "6121271406",
                "m2" => 1005.71,
                "Letras pagadas" => 41,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 88263.89,
                "cantidad_pagada" => 121736.11
            ],
            [
                "contrato" => "SF013",
                "L" => 13,
                "manzana" => 1,
                "fecha_contratacion" => "2022-10-02",
                "comprador" => "Rosario Maricela Mancilla Muñoa",
                "telefono" => "6121271406",
                "m2" => 1005.71,
                "Letras pagadas" => 48,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 68333.33,
                "cantidad_pagada" => 141666.67
            ],
            [
                "contrato" => "SF014",
                "L" => 14,
                "manzana" => 1,
                "fecha_contratacion" => "2021-24-08",
                "comprador" => "Eleyde Maria Loreto Navarro",
                "telefono" => "6121578495",
                "m2" => 1005.71,
                "Letras pagadas" => 16,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 159444.44,
                "cantidad_pagada" => 50555.56
            ],
            [
                "contrato" => "SF015",
                "L" => 15,
                "manzana" => 1,
                "fecha_contratacion" => "2021-19-11",
                "comprador" => "Maria Magdalena Olachea Amador",
                "telefono" => "6122265242",
                "m2" => 1398.13,
                "Letras pagadas" => 36,
                "cantidad_total" => 349532.50,
                "anticipo" => 20000.00,
                "letras" => 36,
                "pagare" => 9153.68,
                "saldo" => null,
                "cantidad_pagada" => 349532.50
            ],
            [
                "contrato" => "SF016",
                "L" => 1,
                "manzana" => 2,
                "fecha_contratacion" => "2021-12-08",
                "comprador" => "Ramon Carlos Ojeda Villalobos",
                "telefono" => "6121199949",
                "m2" => 1000.70,
                "Letras pagadas" => 10,
                "cantidad_total" => 250000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 3402.78,
                "saldo" => 210972.22,
                "cantidad_pagada" => 39027.78
            ],
            [
                "contrato" => "SF017",
                "L" => 2,
                "manzana" => 2,
                "fecha_contratacion" => "2021-12-08",
                "comprador" => "Ramon Carlos Ojeda Villalobos",
                "telefono" => "6121199949",
                "m2" => 1000.15,
                "Letras pagadas" => 10,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 176527.78,
                "cantidad_pagada" => 33472.22
            ],
            [
                "contrato" => "SF018",
                "L" => 3,
                "manzana" => 2,
                "fecha_contratacion" => "2021-12-08",
                "comprador" => "Ramon Carlos Ojeda Villalobos",
                "telefono" => "6121199949",
                "m2" => 999.59,
                "Letras pagadas" => 11,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 173680.56,
                "cantidad_pagada" => 36319.44
            ],
            [
                "contrato" => "SF019",
                "L" => 5,
                "manzana" => 2,
                "fecha_contratacion" => "2022-14-07",
                "comprador" => "Genoveva Rocha Juarez",
                "telefono" => "2871000915",
                "m2" => 1005.71,
                "Letras pagadas" => 44,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 79722.22,
                "cantidad_pagada" => 130277.78
            ],
            [
                "contrato" => "SF020",
                "L" => 6,
                "manzana" => 2,
                "fecha_contratacion" => "2021-12-08",
                "comprador" => "Ramon Carlos Ojeda Villalobos",
                "telefono" => "6121199949",
                "m2" => 1005.71,
                "Letras pagadas" => 10,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 176527.78,
                "cantidad_pagada" => 33472.22
            ],
            [
                "contrato" => "SF021",
                "L" => 7,
                "manzana" => 2,
                "fecha_contratacion" => "2021-12-08",
                "comprador" => "Ramon Carlos Ojeda Villalobos",
                "telefono" => "6121199949",
                "m2" => 1005.71,
                "Letras pagadas" => 10,
                "cantidad_total" => 250000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 3402.78,
                "saldo" => 210972.22,
                "cantidad_pagada" => 39027.78
            ],
            [
                "contrato" => "SF075",
                "L" => 1,
                "manzana" => 3,
                "fecha_contratacion" => "2021-31-05",
                "comprador" => "Lina Marisol Arellano Perez",
                "telefono" => "6121574149",
                "m2" => 1012.00,
                "Letras pagadas" => 56,
                "cantidad_total" => 250000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 3402.78,
                "saldo" => 54444.44,
                "cantidad_pagada" => 195555.56
            ],
            [
                "contrato" => "SF076",
                "L" => 2,
                "manzana" => 3,
                "fecha_contratacion" => "2021-31-05",
                "comprador" => "Lina Marisol Arellano Perez",
                "telefono" => "6121574149",
                "m2" => 1012.00,
                "Letras pagadas" => 56,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 45555.56,
                "cantidad_pagada" => 164444.44
            ],
            [
                "contrato" => "SF077",
                "L" => 3,
                "manzana" => 3,
                "fecha_contratacion" => "2021-31-05",
                "comprador" => "Johann Armando Romero Arellano",
                "telefono" => "6122334524",
                "m2" => 1012.00,
                "Letras pagadas" => 56,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 45555.56,
                "cantidad_pagada" => 164444.44
            ],
            [
                "contrato" => "SF078",
                "L" => 4,
                "manzana" => 3,
                "fecha_contratacion" => "2021-11-06",
                "comprador" => "Aranza de Jesus Romero Arellano",
                "telefono" => "6121546291",
                "m2" => 1012.00,
                "Letras pagadas" => 56,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 45555.56,
                "cantidad_pagada" => 164444.44
            ],
            [
                "contrato" => "SF079",
                "L" => 5,
                "manzana" => 3,
                "fecha_contratacion" => "2021-20-09",
                "comprador" => "Jose Trinidad Villegas Zacarias",
                "telefono" => "6121542244",
                "m2" => 1012.00,
                "Letras pagadas" => 37,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 99652.78,
                "cantidad_pagada" => 110347.22
            ],
            [
                "contrato" => "SF080",
                "L" => 6,
                "manzana" => 3,
                "fecha_contratacion" => null,
                "comprador" => null,
                "telefono" => null,
                "m2" => 1296.96,
                "Letras pagadas" => 0,
                "cantidad_total" => 249664.80,
                "anticipo" => null,
                "letras" => 60,
                "pagare" => 4161.08,
                "saldo" => 249664.80,
                "cantidad_pagada" => null
            ],
            [
                "contrato" => "SF081",
                "L" => 7,
                "manzana" => 3,
                "fecha_contratacion" => null,
                "comprador" => null,
                "telefono" => null,
                "m2" => 1301.79,
                "Letras pagadas" => null,
                "cantidad_total" => 227813.25,
                "anticipo" => null,
                "letras" => 60,
                "pagare" => 3796.89,
                "saldo" => 227813.25,
                "cantidad_pagada" => null
            ],
            [
                "contrato" => "SF082",
                "L" => 8,
                "manzana" => 3,
                "fecha_contratacion" => "2021-03-01",
                "comprador" => "Sofia Viviana Mendoza Luna",
                "telefono" => "6121311961",
                "m2" => 1012.00,
                "Letras pagadas" => 61,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 31319.44,
                "cantidad_pagada" => 178680.56
            ],
            [
                "contrato" => "SF083",
                "L" => 9,
                "manzana" => 3,
                "fecha_contratacion" => "2020-23-10",
                "comprador" => "Virginia Aguilar Ramirez",
                "telefono" => "6121977469",
                "m2" => 1012.00,
                "Letras pagadas" => 63,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 25625.00,
                "cantidad_pagada" => 184375.00
            ],
            [
                "contrato" => "SF084",
                "L" => 10,
                "manzana" => 3,
                "fecha_contratacion" => "2020-21-10",
                "comprador" => "Adan Karin Carmona",
                "telefono" => "6121537813",
                "m2" => 1012.00,
                "Letras pagadas" => 57,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 42708.33,
                "cantidad_pagada" => 167291.67
            ],
            [
                "contrato" => "SF085",
                "L" => 11,
                "manzana" => 3,
                "fecha_contratacion" => "2020-21-10",
                "comprador" => "Alma Delia Juarez Mendivil",
                "telefono" => "6122205840",
                "m2" => 1012.00,
                "Letras pagadas" => 64,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 22777.78,
                "cantidad_pagada" => 187222.22
            ],
            [
                "contrato" => "SF086",
                "L" => 12,
                "manzana" => 3,
                "fecha_contratacion" => "2020-21-10",
                "comprador" => "Alma Delia Juarez Mendivil",
                "telefono" => "6122205840",
                "m2" => 1012.00,
                "Letras pagadas" => 64,
                "cantidad_total" => 241500.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 3284.72,
                "saldo" => 26277.78,
                "cantidad_pagada" => 215222.22
            ],
            [
                "contrato" => "SF087",
                "L" => 1,
                "manzana" => 4,
                "fecha_contratacion" => null,
                "comprador" => null,
                "telefono" => null,
                "m2" => 857.55,
                "Letras pagadas" => null,
                "cantidad_total" => 150071.25,
                "anticipo" => null,
                "letras" => 60,
                "pagare" => 2501.19,
                "saldo" => 150071.25,
                "cantidad_pagada" => null
            ],
            [
                "contrato" => "SF088",
                "L" => 2,
                "manzana" => 4,
                "fecha_contratacion" => "2021-01-12",
                "comprador" => "Adrian Alfredo Cota",
                "telefono" => "6121554466",
                "m2" => 1012.00,
                "Letras pagadas" => 48,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 68333.33,
                "cantidad_pagada" => 141666.67
            ],
            [
                "contrato" => "SF089",
                "L" => 3,
                "manzana" => 4,
                "fecha_contratacion" => "2022-18-01",
                "comprador" => "Armando Javier Green Zavala",
                "telefono" => "6121172366",
                "m2" => 1012.00,
                "Letras pagadas" => 49,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 65486.11,
                "cantidad_pagada" => 144513.89
            ],
            [
                "contrato" => "SF090",
                "L" => 4,
                "manzana" => 4,
                "fecha_contratacion" => "2022-12-02",
                "comprador" => "Armando Javier Green Zavala",
                "telefono" => "6121172366",
                "m2" => 1012.00,
                "Letras pagadas" => 48,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 68333.33,
                "cantidad_pagada" => 141666.67
            ],
            [
                "contrato" => "SF091",
                "L" => 5,
                "manzana" => 4,
                "fecha_contratacion" => "2022-11-02",
                "comprador" => "Miriam Denisse Razo Rangel",
                "telefono" => "6121083347",
                "m2" => 1012.00,
                "Letras pagadas" => 14,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 165138.89,
                "cantidad_pagada" => 44861.11
            ],
            [
                "contrato" => "SF092",
                "L" => 6,
                "manzana" => 4,
                "fecha_contratacion" => "2020-06-07",
                "comprador" => "Cancelado",
                "telefono" => "6121310725",
                "m2" => 1012.00,
                "Letras pagadas" => 19,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 68,
                "pagare" => 3014.71,
                "saldo" => 147720.59,
                "cantidad_pagada" => 62279.41
            ],
            [
                "contrato" => "SF093",
                "L" => 7,
                "manzana" => 4,
                "fecha_contratacion" => "2021-18-05",
                "comprador" => "Cancelado (Lo quiere el cliente anterior )",
                "telefono" => "6121511152",
                "m2" => 1012.00,
                "Letras pagadas" => 3,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 68,
                "pagare" => 3014.71,
                "saldo" => 195955.88,
                "cantidad_pagada" => 14044.12
            ],
            [
                "contrato" => "SF094",
                "L" => 8,
                "manzana" => 4,
                "fecha_contratacion" => "2023-13-03",
                "comprador" => "Manuel de la Cruz Aragon Montaño",
                "telefono" => null,
                "m2" => 1012.00,
                "Letras pagadas" => 35,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 68,
                "pagare" => 3014.71,
                "saldo" => 99485.29,
                "cantidad_pagada" => 110514.71
            ],
            [
                "contrato" => "SF095",
                "L" => 9,
                "manzana" => 4,
                "fecha_contratacion" => "2019-15-05",
                "comprador" => "Mario Fernando Arvizu Valenzuela",
                "telefono" => "6122053674",
                "m2" => 1012.00,
                "Letras pagadas" => 72,
                "cantidad_total" => 253000.00,
                "anticipo" => 10000.00,
                "letras" => 72,
                "pagare" => 3375.00,
                "saldo" => null,
                "cantidad_pagada" => 253000.00
            ],
            [
                "contrato" => "SF096",
                "L" => 10,
                "manzana" => 4,
                "fecha_contratacion" => "2019-15-05",
                "comprador" => "Manuel Enrique Arvizu Valenzuela",
                "telefono" => "6122032720",
                "m2" => 1012.00,
                "Letras pagadas" => 72,
                "cantidad_total" => 253000.00,
                "anticipo" => 10000.00,
                "letras" => 72,
                "pagare" => 3375.00,
                "saldo" => null,
                "cantidad_pagada" => 253000.00
            ],
            [
                "contrato" => "SF097",
                "L" => 11,
                "manzana" => 4,
                "fecha_contratacion" => "2020-10-10",
                "comprador" => "David Ivan Olachea Dominguez",
                "telefono" => "6123481707",
                "m2" => 1012.00,
                "Letras pagadas" => 66,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 68,
                "pagare" => 3014.71,
                "saldo" => 6029.41,
                "cantidad_pagada" => 203970.59
            ],
            [
                "contrato" => "SF098",
                "L" => 12,
                "manzana" => 4,
                "fecha_contratacion" => "2020-21-10",
                "comprador" => "Deysi Maria Gongora Ruiz",
                "telefono" => "9843177110",
                "m2" => 1012.00,
                "Letras pagadas" => 43,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 68,
                "pagare" => 3014.71,
                "saldo" => 75367.65,
                "cantidad_pagada" => 134632.35
            ],
            [
                "contrato" => "SF099",
                "L" => 13,
                "manzana" => 4,
                "fecha_contratacion" => "2020-06-07",
                "comprador" => "Jose Juan Torres Martinez",
                "telefono" => "6121310725",
                "m2" => 1012.00,
                "Letras pagadas" => 43,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 68,
                "pagare" => 3014.71,
                "saldo" => 75367.65,
                "cantidad_pagada" => 134632.35
            ],
            [
                "contrato" => "SF100",
                "L" => 14,
                "manzana" => 4,
                "fecha_contratacion" => "2021-24-03",
                "comprador" => "Samantha Estefania Alvarez Estrada",
                "telefono" => "6121868768",
                "m2" => 1012.00,
                "Letras pagadas" => 59,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 68,
                "pagare" => 3014.71,
                "saldo" => 27132.35,
                "cantidad_pagada" => 182867.65
            ],
            [
                "contrato" => "SF101",
                "L" => 15,
                "manzana" => 4,
                "fecha_contratacion" => "2022-28-06",
                "comprador" => "Andrea Viridiana Gaynor Lopez",
                "telefono" => "6121767202",
                "m2" => 1012.00,
                "Letras pagadas" => 43,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 82569.44,
                "cantidad_pagada" => 127430.56
            ],
            [
                "contrato" => "SF102",
                "L" => 16,
                "manzana" => 4,
                "fecha_contratacion" => "2022-28-06",
                "comprador" => "Julian Gabriel Geraldo Miranda",
                "telefono" => "6121271406",
                "m2" => 1012.00,
                "Letras pagadas" => 42,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2847.22,
                "saldo" => 85416.67,
                "cantidad_pagada" => 124583.33
            ],
            [
                "contrato" => "SF103",
                "L" => 17,
                "manzana" => 4,
                "fecha_contratacion" => "2021-14-02",
                "comprador" => "Mario Alberto Rivera Rochin",
                "telefono" => "6241252410",
                "m2" => 1012.00,
                "Letras pagadas" => 66,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 66,
                "pagare" => 3106.06,
                "saldo" => null,
                "cantidad_pagada" => 210000.00
            ],
            [
                "contrato" => "SF104",
                "L" => 18,
                "manzana" => 4,
                "fecha_contratacion" => "2021-02-02",
                "comprador" => "Giovani Mateos Mayoral",
                "telefono" => "6241163474",
                "m2" => 1012.00,
                "Letras pagadas" => 59,
                "cantidad_total" => 210000.00,
                "anticipo" => 5000.00,
                "letras" => 60,
                "pagare" => 3416.67,
                "saldo" => 3416.67,
                "cantidad_pagada" => 206583.33
            ],
            [
                "contrato" => "SF105",
                "L" => 19,
                "manzana" => 4,
                "fecha_contratacion" => "2021-02-02",
                "comprador" => "Edith Nieves Lucero",
                "telefono" => "6121551319",
                "m2" => 871.02,
                "Letras pagadas" => 59,
                "cantidad_total" => 215141.94,
                "anticipo" => 5000.00,
                "letras" => 60,
                "pagare" => 3502.37,
                "saldo" => 3502.37,
                "cantidad_pagada" => 211639.57
            ],
            [
                "contrato" => "SF106",
                "L" => 20,
                "manzana" => 4,
                "fecha_contratacion" => "2021-10-07",
                "comprador" => "Edith Nieves Lucero",
                "telefono" => "6121551319",
                "m2" => 849.49,
                "Letras pagadas" => 54,
                "cantidad_total" => 178392.90,
                "anticipo" => 5000.00,
                "letras" => 72,
                "pagare" => 2408.23,
                "saldo" => 43348.23,
                "cantidad_pagada" => 135044.68
            ],
            [
                "contrato" => "SF107",
                "L" => 21,
                "manzana" => 4,
                "fecha_contratacion" => null,
                "comprador" => null,
                "telefono" => null,
                "m2" => 850.62,
                "Letras pagadas" => null,
                "cantidad_total" => 116960.25,
                "anticipo" => null,
                "letras" => null,
                "pagare" => null,
                "saldo" => 116960.25,
                "cantidad_pagada" => null
            ]

        ];

        DB::transaction(function () use ($padronSanFrancisco) {
            $zone = Zone::firstOrCreate([
                "nombre" => "San Francisco",
                "dueno_nombre" => "Dueno san francisco",
            ]);
            foreach ($padronSanFrancisco as $row) {

                $persona = $this->createPerson($row["comprador"], $row["telefono"]);
                $predio = $this->createPredio($row,  $zone);

                if ($row["comprador"] == null) {
                    continue;
                }
                if (preg_match('/cancelado/i', $row["comprador"])) {
                    continue;
                }

                $venta = Venta::create([
                    "folio" => $row["contrato"],
                    "person_id" => $persona->id,
                    "predio_id" => $predio->id,
                    "created_at" => $row["fecha_contratacion"],
                    "costo_lote" => $row["cantidad_total"],
                    "enganche" => $row["anticipo"],
                    "meses_a_pagar" => $row["letras"],
                    "saldo_venta" => $row["saldo"] ?? 0,
                    "estado" => $row["saldo"] == 0 ? "pagado" : "pagando",
                    "user_id" => 1,
                    "metodo_pago" => "meses",
                ]);

                $this->createLetras($venta, $row);
            }
            $this->command->info("Registros insertadost");
        });
    }

    public function validateCreateVenta($row)
    {
        if (preg_match('/^cancelado$/i', $row["comprador"])) {
            return false;
        }
        return true;
    }

    public function createPredio($row, $zone)
    {
        $estado = "";

        if ($row["comprador"] == null) {
            $estado = "disponible";
        }

        if ($row["saldo"] == 0 || $row["saldo"] == null) {
            $estado = "pagado";
        }

        if ($row["saldo"] > 0) {
            $estado = "pagando";
        }

        $predio = Predio::create([
            "manzana" => $row["manzana"],
            "lote" => $row["L"],
            "sup_terr" => $row["m2"],
            "zona_id" => $zone->id,
            "estado" => $estado,
        ]);

        if (preg_match('/^cancelado$/i', $row["comprador"])) {
            $predio->observaciones()->create([
                "observacion" => "Cancelado",
            ]);
        }

        return $predio;
    }

    public function createPerson($name, $telefono)
    {
        if ($name == null) {
            return null;
        }
        $nombre = "";
        $apellido_paterno = "";
        $apellido_materno = "";

        if ($name == "Maria de la Cruz Garcia Flores") {
            $nombre = "Maria de la Cruz";
            $apellido_paterno = "Garcia";
            $apellido_materno = "Flores";
        } else if ($name == "Manuel de la Cruz Aragon Montaño") {
            $nombre = "Manuel de la Cruz";
            $apellido_paterno = "Aragon";
            $apellido_materno = "Montaño";
        }else if ($name == "Aranza de Jesus Romero Arellano"){
            $nombre = "Aranza de Jesus";
            $apellido_paterno = "Romero";
            $apellido_materno = "Arellano";
        } 
        else {
            $array = preg_split('/\s+/', trim($name));

            if (count($array) == 2) {
                $nombre = $array[0];
                $apellido_paterno = $array[1];
            }
            if (count($array) == 3) {
                $nombre = $array[0];
                $apellido_paterno = $array[1];
                $apellido_materno = $array[2];
            }
            if (count($array) == 4) {
                $nombre = $array[0] . " " . $array[1];
                $apellido_paterno = $array[2];
                $apellido_materno = $array[3];
            }
        }

        $persona_existente = Person::where("nombres", $nombre)->where("apellido_paterno", $apellido_paterno)->where("apellido_materno", $apellido_materno)->first();
        $persona = null;

        if ($persona_existente) {
            $persona = $persona_existente;
        } else {
            $persona = Person::create([
                "nombres" => $nombre,
                "apellido_paterno" => $apellido_paterno,
                "apellido_materno" => $apellido_materno,
            ]);

            if ($telefono) {
                $persona->phones()->create([
                    "number" => $telefono,
                    "type" => "celular",
                ]);
            }
        }

        return $persona;
    }

    public function createLetras($venta, $row)
    {
        $monto_por_letra = ($row["cantidad_total"] - $row["anticipo"]) / $row["letras"];
        $saldo_anticipo = $row["cantidad_pagada"] > $row["anticipo"] ? 0 : $row["anticipo"] - $row["cantidad_pagada"];

        $venta->letras()->create([
            "descripcion" => "Anticipo",
            "monto" => $row["anticipo"] ?? 0,
            "saldo" => $saldo_anticipo ?? 0,
            "consecutivo" => 0,
            "tipo" => "anticipo",
            "estado" =>   $saldo_anticipo == 0 ? "pagado" : "pendiente",
            "fecha_vencimiento" => $row["fecha_contratacion"],
        ]);

        for ($i = 0; $i < $row["letras"]; $i++) {
            if ($row["Letras pagadas"] > $i) {
                $fecha = Carbon::createFromFormat('Y-d-m', $row["fecha_contratacion"]);
                $venta->letras()->create([
                    "descripcion" => "Letra " . ($i + 1),
                    "monto" => $monto_por_letra,
                    "saldo" => 0,
                    "consecutivo" => $i + 1,
                    "tipo" => "letra",
                    "estado" => "pagado",
                    "fecha_vencimiento" =>  $fecha->addMonths($i + 1),
                ]);
            } else {
                $letra = $venta->letras()->create([
                    "descripcion" => "Letra " . ($i + 1),
                    "monto" => $monto_por_letra,
                    "saldo" => $monto_por_letra,
                    "consecutivo" => $i + 1,
                    "tipo" => "letra",
                    "estado" => "pendiente",
                    "fecha_vencimiento" =>  $fecha->addMonths($i + 1),
                ]);
                if (($i + 1) === $row["Letras pagadas"] + 1) {
                    $venta->proxima_letra_id = $letra->id;
                    $venta->save();
                }
            }
        }
    }
}
