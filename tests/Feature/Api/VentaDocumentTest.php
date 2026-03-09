<?php

use App\Models\Venta;
use App\Models\Person;
use App\Models\Predio;
use App\Models\User;
use App\Models\Letra;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

it('generates a contract automatically when a venta is created', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $comprador = Person::factory()->create(['nombres' => 'Juan', 'apellido_paterno' => 'Perez']);
    $aval = Person::factory()->create(['nombres' => 'Pedro', 'apellido_paterno' => 'Gomez']);
    $predio = Predio::factory()->create();

    // Simulamos la creación a través del Service o manualmente dentro de una transacción
    $venta = DB::transaction(function () use ($comprador, $aval, $predio, $user) {
        $venta = Venta::create([
            'person_id' => $comprador->id,
            'aval_id' => $aval->id,
            'predio_id' => $predio->id,
            'user_id' => $user->id,
            'metodo_pago' => 'meses',
            'costo_lote' => 100000.00,
            'enganche' => 10000.00,
            'fecha_primer_abono' => now()->addMonth(),
            'meses_a_pagar' => 12,
            'estado' => 'pagando',
        ]);

        Letra::create([
            'venta_id' => $venta->id,
            'descripcion' => 'Letra 1',
            'monto' => 7500.00,
            'fecha_vencimiento' => now()->addMonth(),
            'estado' => 'pendiente',
            'tipo' => 'letra',
            'consecutivo' => 1
        ]);

        return $venta;
    });

    // Como usamos afterCommit, el observer debería dispararse al finalizar la transacción
    $venta->refresh();

    // Verificamos que se haya creado el registro del archivo
    expect($venta->files()->count())->toBe(1);

    $file = $venta->files->first();
    expect($file->title)->toBe('Contrato de Promesa de Venta');

    // Verificamos que el archivo físico exista
    Storage::disk('public')->assertExists($file->path);
});
