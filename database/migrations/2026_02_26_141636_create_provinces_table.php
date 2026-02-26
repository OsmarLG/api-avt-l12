<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('country_id');
            $table->string('name');
            $table->string('shopify_code')->nullable();
            $table->string('fando_code')->nullable();
            $table->string('tax_name')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('tax', 8, 4)->nullable();
            $table->decimal('tax_percentage', 8, 2)->nullable();
            $table->timestamps();

            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
