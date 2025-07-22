<?php

namespace App;

use App\Providers\NotificationService;
use Illuminate\Support\Facades\Auth;

trait SendNotifications
{
    // notify every users
    protected function notifyAllUsers($title, $message, $metadata = null)
    {
        return app(NotificationService::class)->sendAdminNotification($title, $message, Auth::id(),$metadata);
    }

    // notify state users
    protected function notifyByState($title, $message, $state_id, $metadata = null)
    {
        return app(NotificationService::class)->sendStateNotification($title, $message, $state_id, Auth::id(), $metadata);
    }

    // notify single user
    protected function notifyUser($title, $message, $user_id, $metadata = null)
    {
        return app(NotificationService::class)->sendPersonalNotification($title, $message, $user_id, Auth::id(), $metadata);
    }
}
