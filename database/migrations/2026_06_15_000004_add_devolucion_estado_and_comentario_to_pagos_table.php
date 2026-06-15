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
        if (Schema::hasColumn('pagos', 'estado_devolucion')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropColumn('estado_devolucion');
            });
        }

        if (! Schema::hasColumn('pagos', 'comentario_devolucion')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->text('comentario_devolucion')->nullable()->after('id_devolvio');
            });
        }

        Schema::table('pagos', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'cancelado', 'devolucion'])->default('activo')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'cancelado'])->default('activo')->change();
        });

        if (Schema::hasColumn('pagos', 'comentario_devolucion')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropColumn('comentario_devolucion');
            });
        }
    }
};
