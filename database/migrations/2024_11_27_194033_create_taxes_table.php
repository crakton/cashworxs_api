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
		Schema::create('taxes', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->foreignUuid('user_id')->nullable();
			$table->string('tax_type');
			$table->string('tax_name');
			$table->integer('tax_year');
			$table->integer('tax_amount');
			$table->string('tax_status');
			$table->integer('gross_income');
			$table->integer('tax_rate');
			$table->json('tax_metadata');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('taxes');
	}
};
