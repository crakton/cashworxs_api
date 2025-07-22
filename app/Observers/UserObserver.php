<?php

namespace App\Observers;

use App\Models\User;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    protected $userNotificationService;

    public function __construct(UserNotificationService $userNotificationService)
    {
        $this->userNotificationService = $userNotificationService;
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Auto-register user with OneSignal after creation
        try {
            Log::info("Auto-registering new user {$user->id} with OneSignal");
            $this->userNotificationService->registerUserWithOneSignal($user);
        } catch (\Exception $e) {
            Log::error("Failed to auto-register user {$user->id} with OneSignal: " . $e->getMessage());
        }
    }
}
