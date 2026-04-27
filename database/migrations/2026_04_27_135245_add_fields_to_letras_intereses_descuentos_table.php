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
            $table->text('comentario')->nullable();
            $table->enum('estado', ['activo', 'cancelado'])->default('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letras_intereses_descuentos', function (Blueprint $table) {
            $table->dropColumn('comentario');
            $table->dropColumn('estado');
        });
    }
};
