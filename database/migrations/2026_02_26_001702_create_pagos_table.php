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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->decimal('monto', 15, 2);
            $table->foreignId('person_id')->constrained('people');
            $table->enum('estado', ['activo', 'cancelado'])->default('activo');
            $table->text('comentario_cancelacion')->nullable();
            $table->foreignId('id_cancelo')->nullable()->constrained('users');
            $table->string('folio')->nullable();
            $table->date('fecha_pago');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
