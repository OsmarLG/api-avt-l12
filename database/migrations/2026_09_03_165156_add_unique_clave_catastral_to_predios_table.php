<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La clave catastral es el identificador natural del predio: es lo que hace
     * idempotente al importador de padrón. Sin unique, un reintento duplica predios.
     */
    public function up(): void
    {
        $duplicadas = DB::table('predios')
            ->select('clave_catastral', DB::raw('COUNT(*) as total'))
            ->whereNotNull('clave_catastral')
            ->where('clave_catastral', '!=', '')
            ->groupBy('clave_catastral')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('total', 'clave_catastral');

        if ($duplicadas->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede crear el índice único: hay claves catastrales duplicadas en predios. '
                    .'Resuélvelas primero: '
                    .$duplicadas->map(fn ($total, $clave) => "{$clave} ({$total})")->implode(', ')
            );
        }

        Schema::table('predios', function (Blueprint $table) {
            $table->dropIndex('predios_clave_catastral_index');
            $table->unique('clave_catastral', 'predios_clave_catastral_unique');
        });
    }

    public function down(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            $table->dropUnique('predios_clave_catastral_unique');
            $table->index('clave_catastral', 'predios_clave_catastral_index');
        });
    }
};
