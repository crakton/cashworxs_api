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
        Schema::table('users', function (Blueprint $table) {
            $table->string('onesignal_user_id')->nullable()->after('provider_id');
            $table->string('fcm_token')->nullable()->after('onesignal_user_id');
            $table->timestamp('onesignal_registered_at')->nullable()->after('onesignal_user_id');
            $table->foreignUlid('state_id')->nullable()->after('fcm_token');
            $table->string('role')->after('state_id');
            $table->boolean('push_notifications_enabled')->default(true)->after('role');
            $table->json('notification_preferences')->nullable()->after('push_notifications_enabled');
            $table->boolean('can_receive_notifications')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['state_id']); // Important: drop foreign key first
            $table->dropColumn([
                'onesignal_user_id',
                'fcm_token',
                'state_id',
                'role',
                'push_notifications_enabled',
                'notification_preferences',
                'onesignal_registered_at',
                'can_receive_notifications'
            ]);
        });
    }
};