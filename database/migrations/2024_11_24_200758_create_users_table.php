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
		Schema::create('users', function (Blueprint $table) {
			$table->ulid('id')->primary();
			$table->string('full_name');
			$table->string('phone_number')->uniqid()->max(11);
			$table->string('password');
			$table->string('provider')->nullable();
			$table->string('provider_id')->nullable();
			$table->boolean('verified')->default(false);
			$table->boolean('is_admin')->default(false);
			$table->timestamp('last_login_at')->nullable();
			$table->timestamp('phone_verified_at')->nullable();
			$table->rememberToken();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('users');
	}
};
