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
        Schema::table('people', function (Blueprint $table) {
            $table->enum('sexo', ['masculino', 'femenino'])->nullable()->change();
            $table->date('fecha_nacimiento')->nullable()->change();
            $table->integer('edad')->nullable()->change();
            $table->enum('nacionalidad', ['mexicana', 'estadounidense'])->nullable()->change();
            $table->enum('estado_civil', ['soltero', 'casado', 'divorciado', 'viudo', 'union_libre'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->enum('sexo', ['masculino', 'femenino'])->change();
            $table->date('fecha_nacimiento')->change();
            $table->integer('edad')->change();
            $table->enum('nacionalidad', ['mexicana', 'estadounidense'])->change();
            $table->enum('estado_civil', ['soltero', 'casado', 'divorciado', 'viudo', 'union_libre'])->change();
        });
    }
};
