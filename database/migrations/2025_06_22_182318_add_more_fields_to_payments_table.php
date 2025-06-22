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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->after('receipt_no');
            $table->string('gateway')->nullable()->after('status');
            $table->string('gateway_payment_id')->nullable()->after('gateway');
            $table->json('gateway_response')->nullable()->after('gateway_payment_id');
            $table->boolean('verification_status')->nullable()->after('gateway_response');
            
            // Add indexes for better performance
            $table->index('payment_reference');
            $table->index('gateway');
            $table->index(['gateway', 'gateway_payment_id']);
            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payments_payment_reference_index']);
            $table->dropIndex(['payments_gateway_index']);
            $table->dropIndex(['payments_gateway_gateway_payment_id_index']);
            $table->dropIndex(['payments_verification_status_index']);
            $table->dropColumn([
                'payment_reference', 
                'gateway', 
                'gateway_payment_id', 
                'gateway_response',
                'verification_status'
            ]);
        });
    }
};
