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
       Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('gateway');
            $table->string('operation'); // create_invoice, process_payment, verify_payment, webhook
            $table->string('reference')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->string('status'); // success, failed, pending
            $table->text('error_message')->nullable();
            $table->integer('response_time')->nullable(); // in milliseconds
            $table->timestamps();
            
            // Indexes
            $table->index('gateway');
            $table->index('operation');
            $table->index('reference');
            $table->index('status');
            $table->index(['gateway', 'operation']);
            $table->index(['gateway', 'reference']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_logs');
    }
};
