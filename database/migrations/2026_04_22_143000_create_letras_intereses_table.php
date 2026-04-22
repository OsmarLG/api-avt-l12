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
        Schema::create('letras_intereses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letra_id');
            $table->decimal('monto_bruto', 15, 2)->comment('Monto antes de descuento');
            $table->decimal('monto_neto', 15, 2)->comment('Monto neto después de descuento');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letras_intereses');
    }
};
