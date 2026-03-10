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
            $table->enum('estado', ['pendiente', 'pagado', 'parcial', 'cancelado'])->default('pendiente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letras', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'pagado', 'parcial'])->default('pendiente')->change();
        });
    }

};
