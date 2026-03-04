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
        Schema::table('letras', function (Blueprint $table) {
            $table->index(
                ['venta_id', 'estado', 'fecha_vencimiento'],
                'letras_venta_estado_fecha_idx'
            );
            $table->enum("tipo", ["letra", "anticipo", "contado"])->default("letra");
            $table->date("fecha_expiracion")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letras', function (Blueprint $table) {
            $table->dropIndex('letras_venta_estado_fecha_idx');
            $table->dropColumn(["tipo","fecha_expiracion"]);
        });
    }
};
