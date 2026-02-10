<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('predios', function (Blueprint $table) {
            $table->id();
            $table->string('clave_catastral')->nullable()->index();
            $table->geometry('polygon', subtype: 'polygon', srid: 4326)->nullable(); // using geometry with srid 4326
            $table->double('gid')->nullable();
            $table->string('condicion')->nullable();
            $table->string('tipo_predio')->nullable();
            $table->string('activo')->nullable();
            $table->string('propietario')->nullable();
            $table->string('ubicacion')->nullable();
            $table->double('sup_cons')->nullable();
            $table->double('sup_terr')->nullable();
            $table->double('vc')->nullable();
            $table->double('vt')->nullable();
            $table->double('tasa')->nullable();
            $table->string('manzana')->nullable();
            $table->double('area')->nullable();
            $table->foreignId('zona_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predios');
    }
};
