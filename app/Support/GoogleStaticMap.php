<?php

namespace App\Support;

use App\Models\Predio;
use Illuminate\Support\Facades\Http;

class GoogleStaticMap
{
    /**
     * Obtiene imagen satelital (base64 data URI) del predio vía Google Static Maps API.
     */
    public static function satelliteImageForPredio(?Predio $predio): ?string
    {
        $apiKey = config('services.google_maps.api_key');

        if (! $apiKey || ! $predio) {
            return null;
        }

        $centro = $predio->centroMapa();

        if (! $centro) {
            return null;
        }

        $lat = $centro['lat'];
        $lng = $centro['lng'];

        $query = http_build_query([
            'center' => "{$lat},{$lng}",
            'zoom' => (int) config('services.google_maps.static.zoom', 18),
            'size' => config('services.google_maps.static.size', '640x320'),
            'maptype' => 'satellite',
            'scale' => (int) config('services.google_maps.static.scale', 2),
            'key' => $apiKey,
        ]);

        $query .= '&markers='.rawurlencode("color:red|{$lat},{$lng}");

        $path = $predio->rutaPoligonoGoogleStatic();
        if ($path) {
            $query .= '&path='.rawurlencode($path);
        }

        $url = 'https://maps.googleapis.com/maps/api/staticmap?'.$query;

        try {
            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $contentType = $response->header('Content-Type') ?: 'image/png';
            if (! str_starts_with($contentType, 'image/')) {
                return null;
            }

            return 'data:'.$contentType.';base64,'.base64_encode($response->body());
        } catch (\Throwable) {
            return null;
        }
    }
}
