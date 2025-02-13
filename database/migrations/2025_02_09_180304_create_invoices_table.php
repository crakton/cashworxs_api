<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->dateTime('tdate');
            $table->text('note')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('c_code');
            $table->string('c_name');
            $table->string('c_address');
            $table->string('c_phone');
            $table->string('c_number');
            $table->string('c_email');
            $table->string('client_invoice_number')->nullable();
            $table->string('status')->default('pending'); // pending, paid, cancelled
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('i_name');
            $table->string('i_code');
            $table->decimal('i_amount', 10, 2);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
