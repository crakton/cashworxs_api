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
        Schema::create('payment_retries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('original_reference');
            $table->string('retry_reference');
            $table->string('gateway');
            $table->string('operation'); // payment, verification
            $table->integer('attempt_number');
            $table->string('status'); // success, failed, pending
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('original_reference');
            $table->index('retry_reference');
            $table->index('gateway');
            $table->index('attempt_number');
            $table->index('status');
            $table->index('next_retry_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_retries');
    }
};
