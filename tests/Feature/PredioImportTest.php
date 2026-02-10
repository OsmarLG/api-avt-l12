<?php

use App\Models\Predio;
use App\Services\PredioService;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tempPath = storage_path('app/testing/CATASTRO_TEST.geojson');

    // Create a temporary GeoJSON file
    $data = [
        "type" => "FeatureCollection",
        "features" => [
            [
                "type" => "Feature",
                "properties" => [
                    "gid" => 123,
                    "clavecatas" => "TEST_CLAVE_1",
                    "condicion" => "TEST",
                    "tipo_predi" => "U",
                    "activo" => "S",
                    "propietari" => "TEST OWNER",
                    "ubicacion" => "TEST LOCATION",
                    "sup_cons" => 100.0,
                    "sup_terr" => 200.0,
                    "vc" => 1000.0,
                    "vt" => 2000.0,
                    "tasa" => 0.5,
                    "manzana" => "M1",
                    "area" => 300.0
                ],
                "geometry" => [
                    "type" => "MultiPolygon",
                    "coordinates" => [
                        [
                            [
                                [-110.1, 24.1],
                                [-110.2, 24.1],
                                [-110.2, 24.2],
                                [-110.1, 24.2],
                                [-110.1, 24.1]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    if (!File::exists(dirname($this->tempPath))) {
        File::makeDirectory(dirname($this->tempPath), 0755, true);
    }

    File::put($this->tempPath, json_encode($data));

    // Mock the service or configure it? 
    // Since we want to test the full flow including the controller using the service,
    // and the service reads a file, we need to make sure the service instance used by the controller
    // has the correct path.

    $this->service = new PredioService();
    $this->service->setGeoJsonPath($this->tempPath);
    $this->app->instance(PredioService::class, $this->service);
});

afterEach(function () {
    if (File::exists($this->tempPath)) {
        File::delete($this->tempPath);
    }
});

test('it can import predio from geojson via api', function () {
    $response = $this->postJson('/api/predios/import', [
        'claves_catastrales' => ['TEST_CLAVE_1']
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => '1 Predios imported successfully.'
        ])
        ->assertJsonStructure(['data' => [['id', 'clave_catastral', 'propietario']]]);

    expect(Predio::where('clave_catastral', 'TEST_CLAVE_1')->exists())->toBeTrue();

    $predio = Predio::where('clave_catastral', 'TEST_CLAVE_1')->first();
    expect($predio->propietario)->toBe('TEST OWNER');
});

test('it ignores predio allowing partial import', function () {
    $response = $this->postJson('/api/predios/import', [
        'claves_catastrales' => ['NON_EXISTENT_KEY']
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => '0 Predios imported successfully.',
            'data' => []
        ]);
});

test('index endpoint returns paginated predios', function () {
    Predio::factory()->count(5)->create();

    $response = $this->getJson('/api/predios');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'items' => [['id', 'clave_catastral']],
                'meta' => ['current_page', 'total']
            ]
        ]);
});
