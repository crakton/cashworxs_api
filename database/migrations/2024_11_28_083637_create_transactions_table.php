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
		Schema::create('transactions', function (Blueprint $table) {
			$table->ulid('id')->primary();
			$table->foreignUlid('user_id')->constrained()->onDelete('cascade');
			$table->string('fullname')->nullable();
			$table->string('transaction_type'); // invoice/payment
			$table->string('transaction_name');
			$table->decimal('transaction_amount', 10, 2);
			$table->string('transaction_status');
			$table->json('transaction_metadata')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('transactions');
	}
};
