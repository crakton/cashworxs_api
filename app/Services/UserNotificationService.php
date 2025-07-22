<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class UserNotificationService
{
    private $oneSignalAppId;
    private $oneSignalRestApiKey;

    public function __construct()
    {
        $this->oneSignalAppId = config('services.onesignal.rest_app_id');
        $this->oneSignalRestApiKey = config('services.onesignal.rest_api_key');
    }

    /**
     * Register user with OneSignal after user creation using the correct API format
     * Optimized to use minimal tags to avoid hitting tag limits
     */
    public function registerUserWithOneSignal(User $user)
    {
        try {
            // Create external user ID in OneSignal
            $externalUserId = (string) $user->id;
            
            // Simplified payload with minimal tags to avoid hitting limits
            // Only include essential tags
            $payload = [
                'properties' => [
                    'tags' => [
                        'role' => $user->role ?? 'user', // Only keep the most essential tag
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

            // Only add state_id tag if it's essential for your use case
            if ($user->state_id && $this->shouldIncludeStateTag()) {
                $payload['properties']['tags']['state'] = (string) $user->state_id;
            }

            Log::info("OneSignal registration payload for user {$user->id}: " . json_encode($payload));

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->post("https://api.onesignal.com/apps/{$this->oneSignalAppId}/users", $payload);

            $responseData = $response->json();
            
            Log::info("OneSignal user registration response for user {$user->id}: " . json_encode($responseData));

            if ($response->successful()) {
                // Check for OneSignal-specific success indicators
                if (isset($responseData['identity']['external_id']) || isset($responseData['success']) || !isset($responseData['errors'])) {
                    // Update user with OneSignal user ID
                    $user->update([
                        'onesignal_user_id' => $externalUserId,
                        'onesignal_registered_at' => now(),
                    ]);

                    Log::info("User {$user->id} registered with OneSignal successfully");
                    return true;
                } else {
                    Log::error("OneSignal registration failed for user {$user->id}: " . json_encode($responseData));
                    return false;
                }
            } else {
                // Handle specific OneSignal errors
                if (isset($responseData['errors'])) {
                    foreach ($responseData['errors'] as $error) {
                        if ($error['code'] === 'entitlements-tag-limit') {
                            Log::warning("Tag limit exceeded for user {$user->id}, trying with no tags...");
                            return $this->registerUserWithMinimalTags($user);
                        }
                    }
                }
                
                Log::error("Failed to register user {$user->id} with OneSignal. HTTP Status: {$response->status()}, Response: " . json_encode($responseData));
                return false;
            }

        } catch (Exception $e) {
            Log::error("OneSignal user registration error for user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register user with absolute minimal configuration (no tags)
     */
    private function registerUserWithMinimalTags(User $user)
    {
        try {
            $externalUserId = (string) $user->id;
            
            // Absolute minimal payload - no tags at all
            $payload = [
                'properties' => [
                    'language' => 'en',
                    'timezone_id' => config('app.timezone', 'UTC'),
                    'country' => 'NG',
                ],
                'identity' => [
                    'external_id' => $externalUserId
                ],
                'subscriptions' => []
            ];

            Log::info("OneSignal minimal registration payload for user {$user->id}: " . json_encode($payload));

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->post("https://api.onesignal.com/apps/{$this->oneSignalAppId}/users", $payload);

            $responseData = $response->json();
            
            if ($response->successful() && !isset($responseData['errors'])) {
                $user->update([
                    'onesignal_user_id' => $externalUserId,
                    'onesignal_registered_at' => now(),
                ]);

                Log::info("User {$user->id} registered with OneSignal successfully (minimal tags)");
                return true;
            } else {
                Log::error("Failed to register user {$user->id} with OneSignal (minimal): " . json_encode($responseData));
                return false;
            }

        } catch (Exception $e) {
            Log::error("OneSignal minimal registration error for user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if state tag should be included based on your business logic
     */
    private function shouldIncludeStateTag()
    {
        // You can implement logic here to determine if state tagging is critical
        // For now, let's disable it to avoid tag limits
        return false;
    }

    /**
     * Add tags to existing user (use this sparingly due to tag limits)
     */
    public function addTagsToUser(User $user, array $tags)
    {
        if (!$user->onesignal_user_id) {
            Log::error("Cannot add tags to user {$user->id}: not registered with OneSignal");
            return false;
        }

        try {
            // Use PATCH to update existing user tags
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->patch("https://api.onesignal.com/apps/{$this->oneSignalAppId}/users/{$user->onesignal_user_id}", [
                'properties' => [
                    'tags' => $tags
                ]
            ]);

            if ($response->successful()) {
                Log::info("Successfully added tags to user {$user->id}: " . json_encode($tags));
                return true;
            } else {
                Log::error("Failed to add tags to user {$user->id}: " . $response->body());
                return false;
            }

        } catch (Exception $e) {
            Log::error("Error adding tags to user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send targeted notification based on user role instead of tags
     */
    public function sendNotificationByRole($role, $message, $heading = null)
    {
        try {
            // Get users with specific role who are registered with OneSignal
            $users = User::where('role', $role)
                         ->whereNotNull('onesignal_user_id')
                         ->pluck('onesignal_user_id')
                         ->toArray();

            if (empty($users)) {
                Log::info("No users found with role {$role} registered with OneSignal");
                return false;
            }

            // Convert to external IDs format expected by OneSignal
            $externalUserIds = array_map('strval', $users);

            $payload = [
                'app_id' => $this->oneSignalAppId,
                'contents' => ['en' => $message],
                'include_external_user_ids' => $externalUserIds,
            ];

            if ($heading) {
                $payload['headings'] = ['en' => $heading];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->post('https://api.onesignal.com/notifications', $payload);

            if ($response->successful()) {
                Log::info("Successfully sent notification to {$role} users: " . count($externalUserIds) . " recipients");
                return true;
            } else {
                Log::error("Failed to send notification to {$role} users: " . $response->body());
                return false;
            }

        } catch (Exception $e) {
            Log::error("Error sending notification to {$role} users: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to specific users by external IDs
     */
    public function sendNotificationToUsers(array $userIds, $message, $heading = null)
    {
        try {
            $externalUserIds = array_map('strval', $userIds);

            $payload = [
                'app_id' => $this->oneSignalAppId,
                'contents' => ['en' => $message],
                'include_external_user_ids' => $externalUserIds,
            ];

            if ($heading) {
                $payload['headings'] = ['en' => $heading];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->post('https://api.onesignal.com/notifications', $payload);

            if ($response->successful()) {
                Log::info("Successfully sent notification to specific users: " . count($externalUserIds) . " recipients");
                return true;
            } else {
                Log::error("Failed to send notification to specific users: " . $response->body());
                return false;
            }

        } catch (Exception $e) {
            Log::error("Error sending notification to specific users: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure user is registered with OneSignal before sending notification
     */
    public function ensureUserRegisteredWithOneSignal(User $user)
    {
        if (empty($user->onesignal_user_id)) {
            Log::info("User {$user->id} not registered with OneSignal, registering now...");
            return $this->registerUserWithOneSignal($user);
        }
        return true;
    }

    /**
     * Update user's push subscription (called from frontend)
     * Using the correct API endpoint and payload structure
     */
    public function updateUserPushSubscription($userId, $subscriptionData)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // Ensure user is registered with OneSignal
        if (!$this->ensureUserRegisteredWithOneSignal($user)) {
            Log::error("Failed to ensure user {$userId} is registered with OneSignal");
            return false;
        }

        try {
            // Use the correct API endpoint for updating user subscriptions
            $payload = [
                'subscription' => [
                    'type' => 'webPush',
                    'enabled' => true,
                    'web_auth' => $subscriptionData['auth'] ?? null,
                    'web_p256' => $subscriptionData['p256dh'] ?? null,
                    'web_url' => $subscriptionData['endpoint'] ?? null,
                ]
            ];

            Log::info("Updating push subscription for user {$userId}: " . json_encode($payload));

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->post("https://api.onesignal.com/apps/{$this->oneSignalAppId}/users/{$user->onesignal_user_id}/subscriptions", $payload);

            $responseData = $response->json();
            Log::info("Push subscription update response for user {$userId}: " . json_encode($responseData));

            if ($response->successful()) {
                Log::info("Updated push subscription for user {$userId}");
                return true;
            } else {
                Log::error("Failed to update push subscription for user {$userId}. HTTP Status: {$response->status()}, Response: " . json_encode($responseData));
                return false;
            }

        } catch (Exception $e) {
            Log::error("Error updating push subscription for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register multiple users with OneSignal (for existing users)
     */
    public function bulkRegisterUsersWithOneSignal()
    {
        $users = User::whereNull('onesignal_user_id')->get();
        $successCount = 0;
        $failureCount = 0;

        Log::info("Starting bulk OneSignal registration for " . $users->count() . " users");

        foreach ($users as $user) {
            if ($this->registerUserWithOneSignal($user)) {
                $successCount++;
                Log::info("Successfully registered user {$user->id} ({$user->full_name}) with OneSignal");
            } else {
                $failureCount++;
                Log::error("Failed to register user {$user->id} ({$user->full_name}) with OneSignal");
            }

            // Add delay to avoid rate limiting
            usleep(500000); // 500ms delay - increased to be safer
        }

        Log::info("Bulk OneSignal registration completed: {$successCount} success, {$failureCount} failed");
        
        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'total_processed' => $users->count()
        ];
    }

    /**
     * Test OneSignal connection and configuration
     */
    public function testOneSignalConnection()
    {
        try {
            Log::info("Testing OneSignal connection...");
            Log::info("App ID: " . $this->oneSignalAppId);
            Log::info("API Key present: " . ($this->oneSignalRestApiKey ? 'Yes' : 'No'));

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->get("https://api.onesignal.com/apps/{$this->oneSignalAppId}");

            $responseData = $response->json();
            
            if ($response->successful()) {
                Log::info("OneSignal connection test successful: " . json_encode($responseData));
                return ['success' => true, 'data' => $responseData];
            } else {
                Log::error("OneSignal connection test failed. HTTP Status: {$response->status()}, Response: " . json_encode($responseData));
                return ['success' => false, 'error' => $responseData];
            }

        } catch (Exception $e) {
            Log::error("OneSignal connection test exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get OneSignal user information
     */
    public function getOneSignalUser($userId)
    {
        $user = User::find($userId);
        if (!$user || !$user->onesignal_user_id) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->get("https://api.onesignal.com/apps/{$this->oneSignalAppId}/users/{$user->onesignal_user_id}");

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error("Failed to get OneSignal user {$userId}: " . $response->body());
                return null;
            }

        } catch (Exception $e) {
            Log::error("Error getting OneSignal user {$userId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get current tag usage statistics
     */
    public function getTagUsageStats()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->oneSignalRestApiKey,
            ])->get("https://api.onesignal.com/apps/{$this->oneSignalAppId}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'app_name' => $data['name'] ?? 'Unknown',
                    'total_users' => $data['players'] ?? 0,
                ];
            } else {
                return ['success' => false, 'error' => $response->body()];
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}