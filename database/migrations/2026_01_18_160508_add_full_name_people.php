<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            DB::statement("
            ALTER TABLE people
            ADD COLUMN fullname VARCHAR(255)
            GENERATED ALWAYS AS (
                TRIM(
                    CONCAT_WS(
                        ' ',
                        nombres,
                        apellido_paterno,
                        apellido_materno
                    )
                )
            ) STORED
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
