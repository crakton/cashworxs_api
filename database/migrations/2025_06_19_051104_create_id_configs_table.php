<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('id_configs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id');
            $table->string('field_name'); // e.g., 'nin', 'bvn', 'drivers_license'
            $table->string('field_label'); // Display name
            $table->enum('field_type', ['text', 'number', 'email', 'phone', 'file'])->default('text');
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable(); // Store validation rules
            $table->string('help_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unique(['organization_id', 'field_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('id_configs');
    }
};