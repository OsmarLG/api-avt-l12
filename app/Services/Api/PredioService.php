<?php

namespace App\Services\Api;

use App\Models\Predio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PredioService
{
    protected string $geoJsonPath;

    public function __construct()
    {
        $this->geoJsonPath = database_path('seeders/CATASTRO.geojson');
    }

    public function setGeoJsonPath(string $path)
    {
        $this->geoJsonPath = $path;
    }


    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Predio::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('clave_catastral', 'like', "%{$search}%")
                ->orWhere('propietario', 'like', "%{$search}%")
                ->orWhere('ubicacion', 'like', "%{$search}%");
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find(Predio $predio): Predio
    {
        return $predio;
    }

    public function create(array $data): Predio
    {
        if (isset($data['geometry']) && is_array($data['geometry'])) {
            $geometry = $data['geometry'];

            if (isset($geometry['type']) && $geometry['type'] === 'MultiPolygon') {
                $ringCoordinates = $geometry['coordinates'][0][0];
                $points = array_map(function ($coord) {
                    return new Point($coord[1], $coord[0]);
                }, $ringCoordinates);

                if ($points[0] != end($points)) {
                    $points[] = $points[0];
                }

                $lineString = new LineString($points);
                $data['polygon'] = new Polygon([$lineString]);
            }
            // Add other geometry types if needed, but starting with MultiPolygon as per user snippet
        } else if (isset($data['polygon']) && is_array($data['polygon'])) {
            // Handle if polygon is passed directly as array of coordinates? 
            // For now assuming input matches the GeoJSON structure expected or is already processed.
        }

        return Predio::create($data);
    }

    public function update(Predio $predio, array $data): Predio
    {
        $predio->update($data);
        return $predio;
    }

    public function delete(Predio $predio): void
    {
        $predio->delete();
    }

    public function selectList(?string $search, int $limit = 20): \Illuminate\Support\Collection
    {
        $limit = max(1, min($limit, 100));

        return Predio::query()
            ->when($search, function (Builder $q) use ($search) {
                $q->where('clave_catastral', 'like', "%{$search}%")
                    ->orWhere('propietario', 'like', "%{$search}%");
            })
            ->limit($limit)
            ->get(['id', 'clave_catastral', 'propietario']); // adjust fields as needed
    }


    public function getByDistance(array $filters)
    {
        $latitude = $filters['latitude'] ?? null;
        $longitude = $filters['longitude'] ?? null;
        $distanceInMeters = $filters['distance'] ?? 100;

        if (!$latitude || !$longitude) {
            return collect([]);
        }

        // Create a circle polygon approximation
        // 1 degree ~ 111km -> 111000m
        $distanceDegrees = $distanceInMeters / 111000;
        $numPoints = 36;
        $points = [];

        for ($i = 0; $i < $numPoints; $i++) {
            $angle = deg2rad($i * (360 / $numPoints));
            $pLat = $latitude + $distanceDegrees * cos($angle);
            $pLng = $longitude + $distanceDegrees * sin($angle);
            // Point(lat, lng) ?? Wait, Eloquent Spatial Point is usually (lat, lng) if using srid 4326?
            // The previous code had Point($pointLatitude, $pointLongitude).
            // Let's assume (lat, lng) is correct order for the constructor if not specified otherwise.
            $points[] = new Point($pLat, $pLng);
        }
        $points[] = $points[0]; // close ring

        $lineString = new LineString($points);
        $polygon = new Polygon([$lineString]);

        return Predio::query()
            ->whereWithin('polygon', $polygon)
            ->get(); // or paginate? Legacy had paginate(500)
    }


    public function importPredios(array $clavesCatastrales): array
    {
        $path = $this->geoJsonPath;

        if (!file_exists($path)) {
            Log::error("GeoJSON file not found at: {$path}");
            return [];
        }

        $data = file_get_contents($path);
        $geojson = json_decode($data, true);

        if (!isset($geojson['features'])) {
            Log::error("Invalid GeoJSON format");
            return [];
        }

        $imported = [];
        $clavesMap = array_flip($clavesCatastrales);

        foreach ($geojson['features'] as $feature) {
            $clave = $feature['properties']['clavecatas'] ?? null;

            if ($clave && isset($clavesMap[$clave])) {
                // Check if it already exists to avoid duplicates or update?
                // User didn't specify update logic, but usually safe to update or skip.
                // I will update or create.
                $imported[] = $this->createOrUpdatePredio($feature);
            }
        }

        return $imported;
    }

    protected function createOrUpdatePredio(array $feature)
    {
        return DB::transaction(function () use ($feature) {
            $geometry = $feature['geometry'];
            $polygon = null;

            if ($geometry['type'] === 'MultiPolygon') {
                // Taking the first polygon's exterior ring as per legacy logic
                // coordinates structure: [ [ [ [x,y], ... ] ] ]
                // MultiPolygon -> Polygon -> Ring -> Point
                $ringCoordinates = $geometry['coordinates'][0][0];
                $points = array_map(function ($coord) {
                    // GeoJSON is [long, lat], Point expects (lat, long) or (long, lat)?
                    // Eloquent Spatial Point typically takes (lat, lng) in constructor?
                    // Let's check the user example: new Point($point[1], $point[0]);
                    // $point[1] is lat, $point[0] is long.
                    // So Point(lat, lng).
                    return new Point($coord[1], $coord[0]);
                }, $ringCoordinates);

                // Ensure closed ring
                if ($points[0] != end($points)) {
                    $points[] = $points[0];
                }

                $lineString = new LineString($points);
                $polygon = new Polygon([$lineString]);
            }

            return Predio::updateOrCreate(
                ['clave_catastral' => $feature['properties']['clavecatas']],
                [
                    'gid' => $feature['properties']['gid'] ?? null,
                    'condicion' => $feature['properties']['condicion'] ?? null,
                    'tipo_predio' => $feature['properties']['tipo_predi'] ?? null,
                    'activo' => $feature['properties']['activo'] ?? null,
                    'propietario' => $feature['properties']['propietari'] ?? null,
                    'ubicacion' => $feature['properties']['ubicacion'] ?? null,
                    'sup_cons' => $feature['properties']['sup_cons'] ?? null,
                    'sup_terr' => $feature['properties']['sup_terr'] ?? null,
                    'vc' => $feature['properties']['vc'] ?? null,
                    'vt' => $feature['properties']['vt'] ?? null,
                    'tasa' => $feature['properties']['tasa'] ?? null,
                    'manzana' => $feature['properties']['manzana'] ?? null,
                    'area' => $feature['properties']['area'] ?? null,
                    'polygon' => $polygon,
                ]
            );
        });
    }
}
