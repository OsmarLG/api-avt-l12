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
        Schema::table('abonos', function (Blueprint $table) {
            $table->index('letra_id');
            $table->enum("estado", ["activo", "cancelado"])->default("activo");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->dropIndex(['letra_id']);
        });
    }
};
