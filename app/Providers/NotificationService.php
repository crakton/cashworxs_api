<?php

namespace App\Providers;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\ServiceProvider;

class NotificationService extends ServiceProvider
{
    private $oneSignalAppId;
    private $oneSignalRestApiKey;
    private $fcmServerKey;

    public function __construct()
    {
        $this->oneSignalAppId = config('services.onesignal.rest_app_id');
        $this->oneSignalRestApiKey = config('services.onesignal.rest_api_key');
        $this->fcmServerKey = config('services.firebase.server_key');
    }

    /**
     * Send notification to recipients based on type
     */
    public function sendNotification(Notification $notification)
    {
        try {
            // Get recipient users based on notification type
            $recipients = $notification->getRecipientUsers();
            
            Log::info("Notification {$notification->id}: Found {$recipients->count()} recipients");
            
            if ($recipients->isEmpty()) {
                Log::warning("No recipients found for notification: {$notification->id}");
                return ['success' => false, 'message' => 'No recipients found'];
            }

            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($recipients as $user) {
                Log::info("Processing user {$user->id}: OneSignal={$user->onesignal_user_id}, FCM=" . ($user->fcm_token ? 'present' : 'null'));
                
                // Auto-register user with OneSignal if not already registered
                if (empty($user->onesignal_user_id)) {
                    Log::info("Auto-registering user {$user->id} with OneSignal");
                    $this->autoRegisterUserWithOneSignal($user);
                    // Refresh user data
                    $user = $user->fresh();
                }
                
                // Create recipient record
                $recipient = NotificationRecipient::create([
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'status' => NotificationRecipient::STATUS_PENDING,
                ]);

                try {
                    $sent = false;
                    $attemptedMethods = [];

                    // Try OneSignal first if user has OneSignal ID
                    if ($user->onesignal_user_id) {
                        $attemptedMethods[] = 'OneSignal';
                        Log::info("Attempting OneSignal for user {$user->id}");
                        $result = $this->sendOneSignalNotification($notification, $user);
                        if ($result['success']) {
                            $sent = true;
                            Log::info("OneSignal success for user {$user->id}");
                        } else {
                            Log::warning("OneSignal failed for user {$user->id}: " . $result['message']);
                        }
                    }

                    // Try FCM if OneSignal failed or not available
                    if (!$sent && $user->fcm_token) {
                        $attemptedMethods[] = 'FCM';
                        Log::info("Attempting FCM for user {$user->id}");
                        $result = $this->sendFCMNotification($notification, $user);
                        if ($result['success']) {
                            $sent = true;
                            Log::info("FCM success for user {$user->id}");
                        } else {
                            Log::warning("FCM failed for user {$user->id}: " . $result['message']);
                        }
                    }

                    if ($sent) {
                        $recipient->update([
                            'status' => NotificationRecipient::STATUS_SENT,
                            'sent_at' => now(),
                        ]);
                        $successCount++;
                    } else {
                        $errorMessage = empty($attemptedMethods) ? 
                            'User has no notification tokens (OneSignal ID or FCM token)' : 
                            'All notification methods failed: ' . implode(', ', $attemptedMethods);
                            
                        $recipient->update([
                            'status' => NotificationRecipient::STATUS_FAILED,
                            'error_details' => ['message' => $errorMessage],
                        ]);
                        $failureCount++;
                        $errors[] = "Failed to send to user {$user->id}: {$errorMessage}";
                        Log::error("Failed to send to user {$user->id}: {$errorMessage}");
                    }

                } catch (Exception $e) {
                    $recipient->update([
                        'status' => NotificationRecipient::STATUS_FAILED,
                        'error_details' => ['message' => $e->getMessage()],
                    ]);
                    $failureCount++;
                    $errors[] = "Error for user {$user->id}: " . $e->getMessage();
                    Log::error("Notification send error for user {$user->id}: " . $e->getMessage());
                }
            }

            // Update notification status
            $notification->update([
                'status' => $failureCount === 0 ? Notification::STATUS_SENT : 
                           ($successCount === 0 ? Notification::STATUS_FAILED : Notification::STATUS_SENT)
            ]);

            return [
                'success' => $successCount > 0,
                'message' => "Sent to {$successCount} users, failed for {$failureCount} users",
                'details' => [
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'errors' => $errors
                ]
            ];

        } catch (Exception $e) {
            Log::error("Notification service error: " . $e->getMessage());
            $notification->update(['status' => Notification::STATUS_FAILED]);
            
            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Auto-register user with OneSignal during notification sending
     * Updated to use correct API format
     */
    private function autoRegisterUserWithOneSignal(User $user)
    {
        try {
            $externalUserId = (string) $user->id;
            
            $payload = [
                'properties' => [
                    'tags' => [
                        'user_id' => $user->id,
                        'user_type' => $user->role ?? 'user',
                        'state_id' => $user->state_id ?? null,
                        'created_at' => $user->created_at->toISOString(),
                    ],
                    'language' => 'en',
                    'timezone_id' => config('app.timezone', 'UTC'),
                    'country' => 'NG',
                ],
                'identity' => [
                    'external_id' => $externalUserId
                ],
                'subscriptions' => []
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->post("https://api.onesignal.com/apps/{$this->oneSignalAppId}/users", $payload);

            $responseData = $response->json();

            if ($response->successful() && (isset($responseData['identity']['external_id']) || isset($responseData['success']))) {
                $user->update([
                    'onesignal_user_id' => $externalUserId,
                    'onesignal_registered_at' => now(),
                ]);
                Log::info("Auto-registered user {$user->id} with OneSignal");
                return true;
            } else {
                Log::error("Failed to auto-register user {$user->id} with OneSignal. HTTP Status: {$response->status()}, Response: " . json_encode($responseData));
                return false;
            }

        } catch (Exception $e) {
            Log::error("Auto-registration error for user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification via OneSignal using correct REST API v1 endpoints
     */
    private function sendOneSignalNotification(Notification $notification, User $user)
    {
        try {
            Log::info("OneSignal Config - App ID: {$this->oneSignalAppId}, API Key: " . ($this->oneSignalRestApiKey ? 'present' : 'missing'));
            
            // Use the correct REST API v1 endpoint for sending notifications
            $payload = [
                'app_id' => $this->oneSignalAppId,
                'target_channel' => 'push',
                'headings' => [
                    'en' => $notification->title
                ],
                'contents' => [
                    'en' => $notification->message
                ],
                'include_aliases' => [
                    'external_id' => [$user->onesignal_user_id]
                ]
            ];

            // Add metadata if available
            if ($notification->metadata) {
                $payload['data'] = $notification->metadata;
                
                // Add image if provided
                if (isset($notification->metadata['image_url'])) {
                    $payload['big_picture'] = $notification->metadata['image_url'];
                    $payload['large_icon'] = $notification->metadata['image_url'];
                }

                // Add action URL if provided
                if (isset($notification->metadata['action_url'])) {
                    $payload['url'] = $notification->metadata['action_url'];
                }
            }

            Log::info("OneSignal notification payload: " . json_encode($payload));

            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->post('https://api.onesignal.com/notifications', $payload);

            $responseData = $response->json();
            
            Log::info("OneSignal notification response: " . json_encode($responseData));

            if ($response->successful() && isset($responseData['id'])) {
                return ['success' => true, 'response' => $responseData];
            } else {
                $errorMessage = isset($responseData['errors']) ? 
                    json_encode($responseData['errors']) : 
                    'Unknown OneSignal error';
                return ['success' => false, 'message' => $errorMessage];
            }

        } catch (Exception $e) {
            Log::error("OneSignal exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send notification via FCM (unchanged)
     */
    private function sendFCMNotification(Notification $notification, User $user)
    {
        try {
            Log::info("FCM Config - Server Key: " . ($this->fcmServerKey ? 'present' : 'missing'));
            
            $data = [
                'to' => $user->fcm_token,
                'notification' => [
                    'title' => $notification->title,
                    'body' => $notification->message,
                ],
                'data' => [
                    'notification_id' => $notification->id,
                    'type' => $notification->type,
                ]
            ];

            // Add metadata if available
            if ($notification->metadata) {
                $data['data'] = array_merge($data['data'], $notification->metadata);
                
                // Add image if provided
                if (isset($notification->metadata['image_url'])) {
                    $data['notification']['image'] = $notification->metadata['image_url'];
                }

                // Add click action if provided
                if (isset($notification->metadata['action_url'])) {
                    $data['notification']['click_action'] = $notification->metadata['action_url'];
                }
            }

            Log::info("FCM payload: " . json_encode($data));

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $data);

            $responseData = $response->json();
            
            Log::info("FCM response: " . json_encode($responseData));

            if ($response->successful() && isset($responseData['success']) && $responseData['success'] > 0) {
                return ['success' => true, 'response' => $responseData];
            } else {
                $errorMessage = isset($responseData['results'][0]['error']) ? 
                    $responseData['results'][0]['error'] : 
                    'Unknown FCM error';
                return ['success' => false, 'message' => $errorMessage];
            }

        } catch (Exception $e) {
            Log::error("FCM exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ... rest of your methods remain the same
    
    /**
     * Create and send admin notification (to all users)
     */
    public function sendAdminNotification($title, $message, $senderId, $metadata = null, $scheduleAt = null)
    {
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => Notification::TYPE_ADMIN,
            'sender_id' => $senderId,
            'metadata' => $metadata,
            'scheduled_at' => $scheduleAt,
            'status' => $scheduleAt ? Notification::STATUS_DRAFT : Notification::STATUS_DRAFT,
        ]);

        if (!$scheduleAt) {
            return $this->sendNotification($notification);
        }

        return ['success' => true, 'message' => 'Notification scheduled successfully', 'notification_id' => $notification->id];
    }

    /**
     * Create and send state-based notification
     */
    public function sendStateNotification($title, $message, $state_id, $senderId, $metadata = null, $scheduleAt = null)
    {
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => Notification::TYPE_STATE,
            'state_id' => $state_id,
            'sender_id' => $senderId,
            'metadata' => $metadata,
            'scheduled_at' => $scheduleAt,
            'status' => $scheduleAt ? Notification::STATUS_DRAFT : Notification::STATUS_DRAFT,
        ]);

        if (!$scheduleAt) {
            return $this->sendNotification($notification);
        }

        return ['success' => true, 'message' => 'Notification scheduled successfully', 'notification_id' => $notification->id];
    }

    /**
     * Create and send personal notification
     */
    public function sendPersonalNotification($title, $message, $userId, $senderId, $metadata = null, $scheduleAt = null)
    {
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => Notification::TYPE_PERSONAL,
            'user_id' => $userId,
            'sender_id' => $senderId,
            'metadata' => $metadata,
            'scheduled_at' => $scheduleAt,
            'status' => $scheduleAt ? Notification::STATUS_DRAFT : Notification::STATUS_DRAFT,
        ]);

        if (!$scheduleAt) {
            return $this->sendNotification($notification);
        }

        return ['success' => true, 'message' => 'Notification scheduled successfully', 'notification_id' => $notification->id];
    }

    /**
     * Process scheduled notifications
     */
    public function processScheduledNotifications()
    {
        $scheduledNotifications = Notification::scheduled()->get();
        $results = [];

        foreach ($scheduledNotifications as $notification) {
            $result = $this->sendNotification($notification);
            $results[] = [
                'notification_id' => $notification->id,
                'result' => $result
            ];
        }

        return $results;
    }

    /**
     * Mark notification as read for a user
     */
    public function markAsRead($notificationId, $userId)
    {
        $recipient = NotificationRecipient::where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($recipient) {
            $recipient->update([
                'status' => NotificationRecipient::STATUS_READ,
                'read_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get user's notifications
     */
    public function getUserNotifications($userId, $limit = 20, $offset = 0)
    {
        return NotificationRecipient::with(['notification', 'notification.sender'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats($notificationId)
    {
        $stats = NotificationRecipient::where('notification_id', $notificationId)
            ->selectRaw('
                status,
                COUNT(*) as count
            ')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => array_sum($stats),
            'pending' => $stats[NotificationRecipient::STATUS_PENDING] ?? 0,
            'sent' => $stats[NotificationRecipient::STATUS_SENT] ?? 0,
            'delivered' => $stats[NotificationRecipient::STATUS_DELIVERED] ?? 0,
            'read' => $stats[NotificationRecipient::STATUS_READ] ?? 0,
            'failed' => $stats[NotificationRecipient::STATUS_FAILED] ?? 0,
        ];
    }

    /**
     * Update user's notification tokens
     */
    public function updateUserTokens($userId, $onesignalId = null, $fcmToken = null)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $updateData = [];
        if ($onesignalId !== null) {
            $updateData['onesignal_user_id'] = $onesignalId;
        }
        if ($fcmToken !== null) {
            $updateData['fcm_token'] = $fcmToken;
        }

        if (!empty($updateData)) {
            $user->update($updateData);
            return true;
        }

        return false;
    }

    /**
     * Update user's notification preferences
     */
    public function updateNotificationPreferences($userId, $preferences)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $user->update([
            'notification_preferences' => $preferences,
            'push_notifications_enabled' => $preferences['push_enabled'] ?? true,
        ]);

        return true;
    }
}