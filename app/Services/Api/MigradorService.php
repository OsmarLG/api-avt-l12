<?php

namespace App\Services\Api;

use App\Models\Abono;
use App\Models\Letra;
use App\Models\Pago;
use App\Models\Person;
use App\Models\Predio;
use App\Models\Venta;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        [$venta, $row] = $this->createVenta($row, $persona, $predio);
        $this->createLetras($venta, $row);
      }
      //$this->command->info("Registros insertadost");
    });
  }

  /**
   * Completa contrato, letras, cantidad_pagada y saldo cuando vienen nulos/vacíos,
   * persiste la venta y devuelve la fila ya coherente para createLetras.
   *
   * @return array{Venta, array<string, mixed>}
   */
  public function createVenta(array $row, Person $persona, Predio $predio): array
  {
    $zone = $predio->zone()->firstOrFail();

    $row = $this->normalizarDatosVentaMigracion($row, $zone);

    $saldo = (float) ($row['saldo'] ?? 0);

    $venta = Venta::create([
      'folio' => $row['contrato'],
      'person_id' => $persona->id,
      'predio_id' => $predio->id,
      'created_at' => $row['fecha_contratacion'],
      'costo_lote' => $row['cantidad_total'],
      'enganche' => $row['anticipo'],
      'meses_a_pagar' => $row['letras'],
      'saldo_venta' => $saldo,
      'estado' => $saldo == 0 ? 'pagado' : 'pagando',
      'user_id' => 1,
      'metodo_pago' => 'meses',
    ]);

    return [$venta, $row];
  }

  /**
   * Deriva campos faltantes para migración: folio (contrato), meses (letras),
   * cantidad pagada y saldo pendiente.
   */
  public function normalizarDatosVentaMigracion(array $row, Zone $zone): array
  {
    $anticipo = isset($row['anticipo']) && $row['anticipo'] !== '' && $row['anticipo'] !== null
      ? (float) $row['anticipo']
      : 0.0;
    $cantidadTotal = (float) ($row['cantidad_total'] ?? 0);
    $pagare = isset($row['pagare']) && $row['pagare'] !== '' && $row['pagare'] !== null
      ? (float) $row['pagare']
      : 0.0;
    $letrasPagadas = isset($row['Letras pagadas']) && $row['Letras pagadas'] !== '' && $row['Letras pagadas'] !== null
      ? (int) $row['Letras pagadas']
      : 0;

    if ($row['cantidad_pagada'] === null || $row['cantidad_pagada'] === '') {
      $row['cantidad_pagada'] = $letrasPagadas * $pagare + $anticipo;
    } else {
      $row['cantidad_pagada'] = (float) $row['cantidad_pagada'];
    }

    if ($row['anticipo'] === null || $row['anticipo'] === '') {
      $row['anticipo'] = $anticipo;
    } else {
      $row['anticipo'] = (float) $row['anticipo'];
    }

    $montoFinanciar = $cantidadTotal - (float) $row['anticipo'];

    if ($row['letras'] === null || $row['letras'] === '') {
      if ($pagare > 0 && $montoFinanciar > 0) {
        // División casi nunca es entera: ceil = meses mínimos para cubrir el financiamiento con pagare fijo
        $row['letras'] = (int) max(1, ceil($montoFinanciar / $pagare));
      } elseif ($pagare > 0 && $montoFinanciar <= 0) {
        $row['letras'] = 0;
      } else {
        $row['letras'] = isset($row['letras']) ? (int) $row['letras'] : 0;
      }
    } else {
      $row['letras'] = (int) $row['letras'];
    }

    if ($row['saldo'] === null || $row['saldo'] === '') {
      // Saldo pendiente = total menos pagado (equivale a lo que suele pedirse como "saldo a favor de la deuda")
      $row['saldo'] = max(0.0, $cantidadTotal - (float) $row['cantidad_pagada']);
    } else {
      $row['saldo'] = (float) $row['saldo'];
    }

    if ($row['contrato'] === null || $row['contrato'] === '') {
      $pref = $this->inicialesNombreZona($zone->nombre);
      $n = $this->siguienteConsecutivoFolioZona($zone);
      $row['contrato'] = $pref . '-' . $n;
    }

    $row['Letras pagadas'] = $letrasPagadas;

    return $row;
  }

  private function inicialesNombreZona(string $nombre): string
  {
    $nombre = trim($nombre);
    if ($nombre === '') {
      return 'Z';
    }

    $parts = preg_split('/\s+/u', $nombre);
    $pref = '';
    foreach ($parts as $part) {
      if ($part === '') {
        continue;
      }
      $first = mb_substr($part, 0, 1, 'UTF-8');
      if (preg_match('/^\p{L}$/u', $first)) {
        $pref .= mb_strtoupper($first, 'UTF-8');
      } elseif (ctype_digit($part)) {
        $pref .= $part;
      } elseif (preg_match('/^(\d+)/', $part, $m)) {
        $pref .= $m[1];
      }
    }

    return $pref !== '' ? $pref : 'Z';
  }

  private function siguienteConsecutivoFolioZona(Zone $zone): int
  {
    return (int) Venta::query()
      ->whereHas('predio', fn($q) => $q->where('zona_id', $zone->id))
      ->count() + 1;
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
    $nLetras = (int) $row['letras'];
    $cantidadTotal = (float) ($row['cantidad_total'] ?? 0);
    $anticipo = (float) ($row['anticipo'] ?? 0);
    $montoFinanciar = $cantidadTotal - $anticipo;

    $monto_por_letra = $nLetras > 0
      ? $montoFinanciar / $nLetras
      : 0.0;
    $saldo_anticipo = (float) $row['cantidad_pagada'] > (float) $row['anticipo']
      ? 0
      : (float) $row['anticipo'] - (float) $row['cantidad_pagada'];

    $pagareFijo = null;
    if (isset($row['pagare']) && $row['pagare'] !== '' && $row['pagare'] !== null) {
      $p = (float) $row['pagare'];
      if ($p > 0 && $nLetras > 0) {
        $pagareFijo = $p;
      }
    }

    $fechaBase = $row["fecha_contratacion"]
      ? Carbon::parse($row["fecha_contratacion"])
      : now();

    $letraAnticipo = $venta->letras()->create([
      "descripcion" => "Anticipo",
      "monto" => $row["anticipo"] ?? 0,
      "saldo" => $saldo_anticipo ?? 0,
      "consecutivo" => 0,
      "tipo" => "anticipo",
      "estado" =>   $saldo_anticipo == 0 ? "pagado" : "pendiente",
      "fecha_vencimiento" => $row["fecha_contratacion"],
    ]);

    $montoAbonadoAnticipo = $anticipo > 0
      ? round(max(0.0, $anticipo - $saldo_anticipo), 2)
      : 0.0;
    if ($montoAbonadoAnticipo > 0) {
      $this->registrarPagoMigracion($venta, $letraAnticipo, $montoAbonadoAnticipo, $fechaBase);
    }

    for ($i = 0; $i < $nLetras; $i++) {
      $fecha = $fechaBase->copy()->addMonths($i + 1);

      if ($pagareFijo !== null) {
        $esUltima = ($i === $nLetras - 1);
        $montoLetra = $esUltima
          ? round(max(0.0, $montoFinanciar - ($nLetras - 1) * $pagareFijo), 2)
          : $pagareFijo;
      } else {
        $montoLetra = $monto_por_letra;
      }

      if ((int) $row['Letras pagadas'] > $i) {
        $letra = $venta->letras()->create([
          "descripcion" => "Letra " . ($i + 1),
          "monto" => $montoLetra,
          "saldo" => 0,
          "consecutivo" => $i + 1,
          "tipo" => "letra",
          "estado" => "pagado",
          "fecha_vencimiento" =>  $fecha,
        ]);
        $this->registrarPagoMigracion($venta, $letra, $montoLetra, $fecha);
      } else {
        $letra = $venta->letras()->create([
          "descripcion" => "Letra " . ($i + 1),
          "monto" => $montoLetra,
          "saldo" => $montoLetra,
          "consecutivo" => $i + 1,
          "tipo" => "letra",
          "estado" => "pendiente",
          "fecha_vencimiento" =>  $fecha,
        ]);
        if (($i + 1) === (int) $row['Letras pagadas'] + 1) {
          $venta->proxima_letra_id = $letra->id;
          $venta->save();
        }
      }
    }

    $venta->calcularCache();
  }

  private function registrarPagoMigracion(Venta $venta, Letra $letra, float $monto, Carbon $fecha): void
  {
    $monto = round($monto, 2);
    if ($monto <= 0) {
      return;
    }

    $folio = 'MIG-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

    $pago = Pago::create([
      'monto' => $monto,
      'recibi' => $monto,
      'cambio' => 0,
      'referenica' => 'Migración',
      'person_id' => $venta->person_id,
      'estado' => 'activo',
      'folio' => $folio,
      'fecha_pago' => $fecha,
      "created_at" => $fecha,
      'user_id' => $venta->user_id ?? 1,
      'metodo_pago' => 'efectivo',
    ]);

    Abono::create([
      'pago_id' => $pago->id,
      'letra_id' => $letra->id,
      'monto' => $monto,
      'estado' => 'activo',
      "created_at" => $fecha
    ]);

    $pagoService = new PagoService();
    $pagoService->savePagoTicket($pago->id);
  }
}
