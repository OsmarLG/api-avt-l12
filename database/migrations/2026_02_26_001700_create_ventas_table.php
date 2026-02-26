<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->comment('Comprador')->constrained('people');
            $table->foreignId('aval_id')->comment('Aval')->constrained('people');
            $table->foreignId('predio_id')->constrained('predios');
            $table->enum('estado', ['pagando', 'cancelado', 'pagado'])->default('pagando');
            $table->foreignId('user_id')->comment('Usuario que registro la venta')->constrained('users');
            $table->enum('metodo_pago', ['meses', 'contado']);
            $table->decimal('costo_lote', 15, 2);
            $table->decimal('enganche', 15, 2);
            $table->date('fecha_primer_abono');
            $table->integer('meses_a_pagar');
            $table->foreignId('id_cancelo')->nullable()->comment('Usuario que cancelo la venta')->constrained('users');
            $table->text('comentario_cancelacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
