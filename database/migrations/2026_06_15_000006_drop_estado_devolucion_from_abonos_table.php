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
        if (Schema::hasColumn('abonos', 'estado_devolucion')) {
            Schema::table('abonos', function (Blueprint $table) {
                $table->dropColumn('estado_devolucion');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->enum('estado_devolucion', ['activo', 'devuelto'])->default('activo')->after('estado');
        });
    }
};
