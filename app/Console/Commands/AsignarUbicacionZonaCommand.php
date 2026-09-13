<?php

namespace App\Console\Commands;

use App\Models\Zone;
use Illuminate\Console\Command;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;

class AsignarUbicacionZonaCommand extends Command
{
    protected $signature = 'zona:asignar-ubicacion';

    protected $description = 'Asigna coordenadas (lat, lng) al campo ubicacion de una zona';

    public function handle(): int
    {
        $zonaId = $this->ask('ID de la zona');

        if (! is_numeric($zonaId)) {
            $this->error('El ID debe ser un número.');

            return self::FAILURE;
        }

        $zona = Zone::find($zonaId);

        if ($zona === null) {
            $this->error("No existe una zona con ID {$zonaId}.");

            return self::FAILURE;
        }

        $this->info("Zona: {$zona->nombre} (ID {$zona->id})");

        $coordenadas = $this->ask('Coordenadas (lat, lng)', '24.113930079392645, -110.32278576574339');

        if (! preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $coordenadas, $matches)) {
            $this->error('Formato inválido. Usa: latitud, longitud (ej. 24.11393, -110.32278)');

            return self::FAILURE;
        }

        $latitud = (float) $matches[1];
        $longitud = (float) $matches[2];

        if ($latitud < -90 || $latitud > 90 || $longitud < -180 || $longitud > 180) {
            $this->error('Coordenadas fuera de rango (lat -90..90, lng -180..180).');

            return self::FAILURE;
        }

        $zona->ubicacion = new Point($latitud, $longitud, Srid::WGS84);
        $zona->save();

        $this->info("Ubicación asignada a la zona {$zona->id}: {$latitud}, {$longitud}");

        return self::SUCCESS;
    }
}
