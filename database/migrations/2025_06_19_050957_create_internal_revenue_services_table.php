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
       Schema::create('internal_revenue_services', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('irs_name');
            $table->string('short_name');
            $table->ulid('state_id');
            $table->string('website')->nullable();
            $table->json('contacts'); // Store phone and email arrays
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->unique(['state_id']); // One IRS per state
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_revenue_services');
    }
};
