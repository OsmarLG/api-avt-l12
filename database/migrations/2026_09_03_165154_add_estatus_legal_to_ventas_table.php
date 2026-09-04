<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `ventas.estado` guarda el estado financiero (pagando/pagado/cancelado).
     * "Escriturado" e "INSUS" son estados legales y conviven con cualquier estado
     * financiero, por eso viven en su propia columna.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('estatus_legal', ['escriturado', 'insus'])
                ->nullable()
                ->after('estado')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['estatus_legal']);
            $table->dropColumn('estatus_legal');
        });
    }
};
