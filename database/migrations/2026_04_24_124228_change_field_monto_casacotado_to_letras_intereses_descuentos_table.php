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
        Schema::table('letras_intereses_descuentos', function (Blueprint $table) {
            $table->dropColumn("monto_casacotado");
            $table->decimal('monto_descontado', 15, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letras_intereses_descuentos', function (Blueprint $table) {
            //
        });
    }
};
