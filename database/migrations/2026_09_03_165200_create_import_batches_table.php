<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora de importaciones: cada lote guarda qué archivo se cargó, con qué
     * opciones y — en import_batch_records — cada fila que tocó, para poder revertir.
     */
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tipo')->default('padron');
            $table->enum('estado', ['previsualizado', 'aplicado', 'revertido'])->default('previsualizado')->index();
            $table->string('archivo_nombre');
            $table->string('archivo_hash', 64)->index();
            $table->foreignId('zona_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('zona_nombre')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('opciones')->nullable();
            $table->json('resumen')->nullable();
            $table->timestamp('aplicado_at')->nullable();
            $table->timestamp('revertido_at')->nullable();
            $table->foreignId('revertido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('import_batch_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('fila')->nullable()->comment('Fila del Excel que originó el registro');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->enum('accion', ['creado', 'actualizado', 'reutilizado'])->index();
            $table->string('referencia')->nullable()->comment('Clave catastral, folio o nombre, para el reporte');
            $table->json('datos_previos')->nullable()->comment('Valores anteriores, para revertir un update');
            $table->timestamps();

            $table->index(['import_batch_id', 'model_type']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_records');
        Schema::dropIfExists('import_batches');
    }
};
