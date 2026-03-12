<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pagos', function (Blueprint $table) {
            // agregar campo enum para método de pago
            $table->enum('metodo_pago', ['efectivo', 'tarjeta_debito', 'tarjeta_credito', 'cheque', 'transferencia'])
                  ->after('monto')
                  ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('metodo_pago');
        });
    }
};
