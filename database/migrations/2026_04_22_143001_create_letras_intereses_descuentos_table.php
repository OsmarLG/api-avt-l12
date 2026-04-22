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
        Schema::create('letras_intereses_descuentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letra_interes_id');
            $table->decimal('porcentaje', 15, 2);
            $table->decimal('monto_casacotado', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letras_intereses_descuentos');
    }
};
