<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->integer('id')->primary()->unique();
            $table->foreignUlid('user_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->integer('mda_id');
            $table->string('mda_code');
            $table->dateTime('tdate');
            $table->decimal('amount', 10, 2);
            $table->string('c_code');
            $table->string('c_name');
            $table->string('c_address');
            $table->string('c_phone');
            $table->string('c_number');
            $table->string('c_email');
            $table->string('client_invoice_number')->nullable();
            $table->tinyInteger('status')->default(0); // 0: unpaid, 1: paid
            $table->text('note')->nullable();
            $table->dateTime('log_time');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->integer('id')->primary()->unique();
            $table->string('invoice_id')->constrained()->onDelete('cascade');
            $table->string('i_code');
            $table->string('i_name');
            $table->decimal('i_amount', 10, 2);
            $table->text('note')->nullable();
            $table->string('i_type')->nullable();
            $table->string('i_org')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable();
            $table->string('invoice_number');
            $table->foreign('invoice_number')->references('invoice_number')->on('invoices');
            $table->string('receipt_no');
            $table->dateTime('tdate');
            $table->decimal('amount', 10, 2);
            $table->text('note')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->dateTime('log_time');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payments');
    }
};
