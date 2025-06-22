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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('status');
            $table->string('gateway_invoice_id')->nullable()->after('gateway');
            $table->json('gateway_response')->nullable()->after('gateway_invoice_id');
            
            // Add indexes for better performance
            $table->index('gateway');
            $table->index(['gateway', 'gateway_invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoices_gateway_index']);
            $table->dropIndex(['invoices_gateway_gateway_invoice_id_index']);
            $table->dropColumn(['gateway', 'gateway_invoice_id', 'gateway_response']);
        });
    }
};
