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
		Schema::create('fees', function (Blueprint $table) {
			$table->ulid('id')->primary();
			$table->foreignUlid('user_id')->nullable();
			$table->string('fee_type');
			$table->string('fee_name');
			$table->integer('fee_amount');
			$table->string('fee_status');
			$table->json('fee_metadata');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('fees');
	}
};
