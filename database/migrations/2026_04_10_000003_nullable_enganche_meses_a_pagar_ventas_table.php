<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('enganche', 15, 2)->nullable()->change();
            $table->integer('meses_a_pagar')->nullable()->change();
            $table->date('fecha_primer_abono')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('enganche', 15, 2)->nullable(false)->change();
            $table->integer('meses_a_pagar')->nullable(false)->change();
            $table->date('fecha_primer_abono')->nullable(false)->change();
        });
    }
};
