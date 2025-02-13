<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->foreign('invoice_number')
                ->references('invoice_number')
                ->on('invoices')
                ->onDelete('cascade');
            $table->dateTime('tdate');
            $table->decimal('amount', 10, 2);
            $table->string('receipt_no')->unique();
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
