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
        // Add custom fields to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('year_of_assessment')->nullable();
            $table->string('irs_id')->nullable();
            $table->string('irs_name')->nullable();
            $table->string('tax_type')->nullable();
            $table->string('fullname')->nullable();
            $table->json('custom_fields')->nullable()->comment('For any additional custom fields');
        });

        // Add custom fields to payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->integer('year_of_assessment')->nullable();
            $table->string('irs_id')->nullable();
            $table->string('irs_name')->nullable();
            $table->string('tax_type')->nullable();
            $table->string('fullname')->nullable();
            $table->json('custom_fields')->nullable()->comment('For any additional custom fields');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove custom fields from invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'year_of_assessment',
                'irs_id',
                'irs_name',
                'tax_type',
                'fullname',
                'custom_fields'
            ]);
        });

        // Remove custom fields from payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'year_of_assessment',
                'irs_id',
                'irs_name',
                'tax_type',
                'fullname',
                'custom_fields'
            ]);
        });
    }
};
