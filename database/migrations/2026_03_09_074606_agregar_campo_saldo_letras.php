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
        Schema::table('letras', function (Blueprint $table) {
            $table->decimal("saldo")->default(0);
            $table->integer("consecutivo")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letras', function (Blueprint $table) {
            $table->dropColumn("saldo");
            $table->dropColumn("consecutivo");
        });
    }
};
