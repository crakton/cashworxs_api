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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('transaction_status');
            $table->string('reference_id')->nullable()->after('gateway');
            
            // Add indexes for better performance
            $table->index('gateway');
            $table->index('reference_id');
            $table->index(['gateway', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['transactions_gateway_index']);
            $table->dropIndex(['transactions_reference_id_index']);
            $table->dropIndex(['transactions_gateway_reference_id_index']);
            $table->dropColumn(['gateway', 'reference_id']);
        });
    }
};
