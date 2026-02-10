<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PredioTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_store_predio_with_all_fields(): void
    {
        $user = User::factory()->create();
        $zone = Zone::factory()->create();

        $data = [
            'clave_catastral' => 'TEST-001',
            'propietario' => 'Juan Perez',
            'ubicacion' => 'Calle Falsa 123',
            'sup_cons' => 150.5,
            'sup_terr' => 300.0,
            'condicion' => 'Bueno',
            'tipo_predio' => 'Urbano',
            'zona_id' => $zone->id,
            // New fields
            'gid' => 1001,
            'activo' => 'Si',
            'vc' => 50000.0,
            'vt' => 100000.0,
            'tasa' => 0.15,
            'manzana' => 'M-05',
            'area' => 300.0,
            // Polygon is complex, skipping for basic test or sending null
            'polygon' => null,
        ];

        $response = $this->actingAs($user)
            ->postJson(route('api.predios.store'), $data);

        $response->assertCreated();
        $this->assertDatabaseHas('predios', ['clave_catastral' => 'TEST-001']);
    }
}
