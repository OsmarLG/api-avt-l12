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
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            // Personal Information
            $table->string('nombres');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->enum('sexo', ['masculino', 'femenino']);
            $table->date('fecha_nacimiento');
            $table->integer('edad');
            $table->enum('nacionalidad', ['mexicana', 'estadounidense']);
            $table->enum('estado_civil', ['soltero', 'casado', 'divorciado', 'viudo', 'union_libre']);
            $table->string('curp')->unique()->nullable();
            $table->string('rfc')->unique()->nullable();
            $table->string('ine')->unique()->nullable();
            $table->string('ocupacion_profesion')->nullable();

            // Birth Information
            $table->string('pais_nacimiento')->nullable();
            $table->string('estado_nacimiento')->nullable();
            $table->string('municipio_nacimiento')->nullable();
            $table->string('localidad_nacimiento')->nullable();

            // Address
            $table->string('calle')->nullable();
            $table->string('numero_interior')->nullable();
            $table->string('numero_exterior')->nullable();
            $table->string('colonia')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('pais_domicilio')->nullable();
            $table->string('estado_domicilio')->nullable();
            $table->string('municipio_domicilio')->nullable();
            $table->string('localidad_domicilio')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
