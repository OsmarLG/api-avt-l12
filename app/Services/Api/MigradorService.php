<?php

namespace App\Services\Api;

use App\Models\Person;
use App\Models\Predio;
use App\Models\Venta;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MigradorService
{
  /*
      este es un ejemplo de campos que debe llevar cada objeto del arreglo de datos

      $datos = [
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
      ]


      $zona = [
          "nombre" => "Etapa 1"
          "dueno_nombre" => "dueno_etapa_1"
        ]
  */


  public function iniciar($datos, $zona)
  {
    DB::transaction(function () use ($datos, $zona) {

      $zone = Zone::firstOrCreate([
        "nombre" => $zona["nombre"],
        "dueno_nombre" => $zona["dueno_nombre"],
      ]);

      foreach ($datos as $row) {

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
      //$this->command->info("Registros insertadost");
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

    if (preg_match('/cancelado/i', $row["comprador"])) {
      $predio->observaciones()->create([
        "observacion" => "predio creado por migracion, el dia (venta cancelada) " . now()->format('d/m/Y') . " cancelado",
      ]);
    } else {
      $predio->observaciones()->create([
        "observacion" => "predio creado por migracion, el dia " . now()->format('d/m/Y'),
      ]);
    }

    return $predio;
  }
  public function createPerson($name, $telefono)
  {
    if (empty(trim($name ?? ''))) {
      return null;
    }

    $array = preg_split('/\s+/', trim($name));
    $count = count($array);

    $nombre           = "";
    $apellido_paterno = "";
    $apellido_materno = "";

    if ($count === 1) {
      $nombre = $array[0];
    } elseif ($count === 2) {
      $nombre           = $array[0];
      $apellido_paterno = $array[1];
    } else {
      // >= 3: últimas 2 posiciones son apellidos, todo lo anterior es el nombre
      $apellido_materno = array_pop($array);
      $apellido_paterno = array_pop($array);
      $nombre           = implode(' ', $array);
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
    $fechaBase = $row["fecha_contratacion"]
      ? Carbon::parse($row["fecha_contratacion"])
      : now();

    for ($i = 0; $i < $row["letras"]; $i++) {
      $fecha = $fechaBase->copy()->addMonths($i);
      if ($row["Letras pagadas"] > $i) {
        $venta->letras()->create([
          "descripcion" => "Letra " . ($i + 1),
          "monto" => $monto_por_letra,
          "saldo" => 0,
          "consecutivo" => $i + 1,
          "tipo" => "letra",
          "estado" => "pagado",
          "fecha_vencimiento" =>  $fecha,
        ]);
      } else {
        $letra = $venta->letras()->create([
          "descripcion" => "Letra " . ($i + 1),
          "monto" => $monto_por_letra,
          "saldo" => $monto_por_letra,
          "consecutivo" => $i + 1,
          "tipo" => "letra",
          "estado" => "pendiente",
          "fecha_vencimiento" =>  $fecha,
        ]);
        if (($i + 1) === $row["Letras pagadas"] + 1) {
          $venta->proxima_letra_id = $letra->id;
          $venta->save();
        }
      }
    }
  }
}
