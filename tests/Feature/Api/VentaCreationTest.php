<?php

use App\Models\Person;
use App\Models\Predio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('puedo crear una venta con letras personalizadas usando comprador_id', function () {
    $user = User::factory()->create();
    $comprador = Person::factory()->create();
    $aval = Person::factory()->create();
    $predio = Predio::factory()->create(['estado' => 'disponible']);

    $payload = [
        'comprador_id' => $comprador->id,
        'aval_id' => $aval->id,
        'predio_id' => $predio->id,
        'metodo_pago' => 'meses',
        'costo_lote' => '120000.00',
        'enganche' => '5000.00',
        'fecha_primer_abono' => '2026-03-15',
        'meses_a_pagar' => '16',
        'letras' => [
            [
                'descripcion' => 'ANTICIPO',
                'monto' => '5000.00',
                'consecutivo' => 0,
                'tipo' => 'anticipo',
                'fecha_expiracion' => '2026-02-28 14:49:27',
            ],
            [
                'descripcion' => 'Letra 1',
                'monto' => 7000,
                'consecutivo' => 1,
                'tipo' => 'letra',
                'fecha_expiracion' => '2026-03-15',
            ],
        ],
    ];

    $this->withoutExceptionHandling();

    $response = actingAs($user)
        ->postJson('/api/ventas', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.person_id', $comprador->id);

    $this->assertDatabaseHas('ventas', [
        'person_id' => $comprador->id,
        'predio_id' => $predio->id,
        'costo_lote' => 120000.00,
    ]);

    $this->assertDatabaseHas('letras', [
        'descripcion' => 'ANTICIPO',
        'monto' => 5000.00,
        'tipo' => 'anticipo',
        'consecutivo' => 0,
        'fecha_vencimiento' => '2026-02-28',
    ]);

    $this->assertDatabaseHas('letras', [
        'descripcion' => 'Letra 1',
        'monto' => 7000.00,
        'tipo' => 'letra',
        'consecutivo' => 1,
        'fecha_vencimiento' => '2026-03-15',
    ]);

    // Verificar que el predio cambió a pagando
    $this->assertDatabaseHas('predios', [
        'id' => $predio->id,
        'estado' => 'pagando',
    ]);
});

it('falla si no se envia comprador_id ni person_id', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->postJson('/api/ventas', [
            'aval_id' => 1,
            'predio_id' => 1,
            'metodo_pago' => 'contado',
            'costo_lote' => 100,
            'enganche' => 10,
            'fecha_primer_abono' => '2025-01-01',
            'meses_a_pagar' => 1,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['comprador_id', 'person_id']);
});
