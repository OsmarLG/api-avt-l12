<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->onDelete('cascade');
            $table->json('ticket');
            $table->timestamps();

            $table->unique('pago_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_tickets');
    }
};

