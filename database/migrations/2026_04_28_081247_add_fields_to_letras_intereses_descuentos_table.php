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
        Schema::table('letras_intereses_descuentos', function (Blueprint $table) {
            $table->string("folio");
            $table->unsignedInteger('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letras_intereses_descuentos', function (Blueprint $table) {
            $table->dropColumn("folio");
            $table->dropColumn("created_by");
        });
    }
};
