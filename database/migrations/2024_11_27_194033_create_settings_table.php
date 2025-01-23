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
		Schema::create('settings', function (Blueprint $table) {
			$table->ulid('id')->primary();
			$table->foreignUlid('user_id')->nullable();
			$table->json('language')->nullable();
			$table->string('currency')->nullable();
			$table->boolean('complete_status')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('settings');
	}
};
