<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnboardingTable extends Migration
{
    public function up()
    {
        Schema::create('onboarding', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable();
            $table->decimal('income', 15, 2)->nullable();
            $table->string('bvn', 11)->nullable();
            $table->string('nin', 11)->nullable();
            $table->json('onboarding_data')->nullable();
            $table->decimal('onboarding_stat')->default(0);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('onboarding');
    }
}
