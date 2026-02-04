<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // SQLite supports adding VIRTUAL generated columns, but not STORED.
            // Also using standard concatenation || for better compatibility if CONCAT_WS is missing.
            DB::statement("
            ALTER TABLE people
            ADD COLUMN fullname VARCHAR(255)
            GENERATED ALWAYS AS (
                TRIM(
                    COALESCE(nombres, '') || ' ' || 
                    COALESCE(apellido_paterno, '') || ' ' || 
                    COALESCE(apellido_materno, '')
                )
            ) VIRTUAL
        ");

            DB::statement("
            CREATE INDEX people_fullname_index
            ON people (fullname)
        ");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            DB::statement("
            DROP INDEX people_fullname_index
            ON people
        ");

            DB::statement("
            ALTER TABLE people
            DROP COLUMN fullname
        ");
        });
    }
};
