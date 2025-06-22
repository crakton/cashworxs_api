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
        Schema::create('notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['admin', 'state', 'personal'])->default('admin');
            $table->string('state')->nullable(); // For state-based notifications
            $table->ulid('user_id')->nullable(); // For personal notifications
            $table->ulid('sender_id'); // Admin/operator who sent the notification
            $table->enum('status', ['draft', 'sent', 'failed'])->default('draft');
            $table->json('metadata')->nullable(); // Additional data like image_url, action_url, etc.
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['type', 'state']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
