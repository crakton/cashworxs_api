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
            $table->json('onboarding_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('onboarding');
    }
}
