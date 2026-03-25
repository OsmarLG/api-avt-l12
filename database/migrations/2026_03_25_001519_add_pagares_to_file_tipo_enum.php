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
        Schema::table('files', function (Blueprint $table) {
            $table->enum("tipo", ["contrato", "anticipo", "sin_tipo", "pagares"])->default("sin_tipo")->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->enum("tipo", ["contrato", "anticipo", "sin_tipo"])->default("sin_tipo")->change();
        });
    }

};
