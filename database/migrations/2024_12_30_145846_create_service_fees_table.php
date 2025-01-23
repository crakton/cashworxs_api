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
        Schema::create('service_fees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->string('state');
            $table->decimal('amount', 10, 2);
            $table->boolean('status');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_fees');
    }
};
