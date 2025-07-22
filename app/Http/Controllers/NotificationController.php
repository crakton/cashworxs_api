<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendNotificationRequest;
use App\Http\Requests\UpdateNotificationTokensRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Providers\NotificationService;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Exception;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;
    protected UserNotificationService $userNotificationService;

    public function __construct(NotificationService $notificationService,UserNotificationService $userNotificationService)
    {
        $this->notificationService = $notificationService;
        $this->userNotificationService = $userNotificationService;

        // Role-based middleware
        $this->middleware('admin')->only([
            'index',
            'store',
            'update',
            'destroy',
            'processScheduled',
            'bulkRegisterUsers'
        ]);

        $this->middleware('role:admin,operator')->only([
            'store',
            'update',
            'destroy'
        ]);

        $this->middleware('auth')->only([
            'getUserNotifications',
            'markAsRead',
            'updateTokens',
            'updatePreferences',
            'subscribeToPush'
        ]);
    }

    /**
     * Subscribe user to push notifications (called from frontend)
     */
    public function subscribeToPush(Request $request): JsonResponse
    {
        $request->validate([
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
            'subscription.keys' => 'required|array',
            'subscription.keys.auth' => 'required|string',
            'subscription.keys.p256dh' => 'required|string',
        ]);

        $userId = Auth::id();
        $subscriptionData = $request->input('subscription');

        $success = $this->userNotificationService->updateUserPushSubscription($userId, [
            'endpoint' => $subscriptionData['endpoint'],
            'auth' => $subscriptionData['keys']['auth'],
            'p256dh' => $subscriptionData['keys']['p256dh'],
        ]);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Push subscription updated successfully' : 'Failed to update push subscription'
        ]);
    }

    /**
     * Bulk register existing users with OneSignal (Admin only)
     */
    public function bulkRegisterUsers(): JsonResponse
    {
        $result = $this->userNotificationService->bulkRegisterUsersWithOneSignal();

        return response()->json([
            'success' => true,
            'message' => 'Bulk registration completed',
            'data' => $result
        ]);
    }



    /**
     * List all notifications (Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::with(['sender', 'user', 'stateInfo'])->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('state_id')) {
            $query->byState($request->state_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $notifications = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    /**
     * Create/send notification
     */
    public function store(SendNotificationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $senderId = Auth::id();

            switch ($data['type']) {
                case 'admin':
                    $result = $this->notificationService->sendAdminNotification(
                        $data['title'], $data['message'], $senderId,
                        $data['metadata'] ?? null, $data['scheduled_at'] ?? null
                    );
                    break;

                case 'state':
                    $result = $this->notificationService->sendStateNotification(
                        $data['title'], $data['message'], $data['state_id'],
                        $senderId, $data['metadata'] ?? null, $data['scheduled_at'] ?? null
                    );
                    break;

                case 'personal':
                    $result = $this->notificationService->sendPersonalNotification(
                        $data['title'], $data['message'], $data['user_id'],
                        $senderId, $data['metadata'] ?? null, $data['scheduled_at'] ?? null
                    );
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid notification type'
                    ], 400);
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ], $result['success'] ? 201 : 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }

     /**
     * Get user's notification status and tokens
     */
    public function getNotificationStatus(): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'onesignal_user_id' => $user->onesignal_user_id,
                'has_fcm_token' => !empty($user->fcm_token),
                'onesignal_registered_at' => $user->onesignal_registered_at,
                'notification_preferences' => $user->notification_preferences ?? [],
                'push_notifications_enabled' => $user->push_notifications_enabled ?? true,
            ]
        ]);
    }

    /**
     * Force register current user with OneSignal
     */
    public function registerWithOneSignal(): JsonResponse
    {
        $user = Auth::user();
        $success = $this->userNotificationService->registerUserWithOneSignal($user);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'User registered with OneSignal successfully' : 'Failed to register with OneSignal'
        ]);
    }

    /**
     * Show a single notification and stats
     */
    public function show($id): JsonResponse
    {
        $notification = Notification::with(['sender', 'user', 'stateInfo', 'recipients.user'])->findOrFail($id);
        $stats = $this->notificationService->getNotificationStats($id);

        return response()->json([
            'success' => true,
            'data' => new NotificationResource($notification),
            'stats' => $stats
        ]);
    }

    /**
     * Update draft notification (e.g., scheduling)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $notification = Notification::findOrFail($id);

        if ($notification->status !== Notification::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft notifications can be updated'
            ], 400);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
            'scheduled_at' => 'sometimes|nullable|date|after:now',
            'metadata' => 'sometimes|array',
        ]);

        $notification->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Notification updated successfully',
            'data' => new NotificationResource($notification)
        ]);
    }

    /**
     * Delete a draft notification
     */
    public function destroy($id): JsonResponse
    {
        $notification = Notification::findOrFail($id);

        if ($notification->status !== Notification::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft notifications can be deleted'
            ], 400);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Fetch authenticated user's notifications
     */
    public function getUserNotifications(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);

        $notifications = $this->notificationService->getUserNotifications($userId, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => $notifications->map(function ($recipient) {
                return [
                    'id' => $recipient->notification->id,
                    'title' => $recipient->notification->title,
                    'message' => $recipient->notification->message,
                    'type' => $recipient->notification->type,
                    'metadata' => $recipient->notification->metadata,
                    'status' => $recipient->status,
                    'sent_at' => $recipient->sent_at,
                    'read_at' => $recipient->read_at,
                    'created_at' => $recipient->notification->created_at,
                    'sender' => [
                        'id' => $recipient->notification->sender->id,
                        'name' => $recipient->notification->sender->full_name,
                    ]
                ];
            })
        ]);
    }

    /**
     * Mark notification as read for user
     */
    public function markAsRead($notificationId): JsonResponse
    {
        $userId = Auth::id();
        $success = $this->notificationService->markAsRead($notificationId, $userId);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification marked as read' : 'Notification not found'
        ]);
    }

    /**
     * Update notification tokens
     */
    public function updateTokens(UpdateNotificationTokensRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $data = $request->validated();

        $success = $this->notificationService->updateUserTokens(
            $userId,
            $data['onesignal_user_id'] ?? null,
            $data['fcm_token'] ?? null
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification tokens updated successfully' : 'Failed to update tokens'
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $request->validate([
            'push_enabled' => 'required|boolean',
            'admin_notifications' => 'boolean',
            'state_notifications' => 'boolean',
            'personal_notifications' => 'boolean',
        ]);

        $success = $this->notificationService->updateNotificationPreferences(
            $userId,
            $request->all()
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification preferences updated successfully' : 'Failed to update preferences'
        ]);
    }

    /**
     * Process all scheduled notifications (e.g., cron)
     */
    public function processScheduled(): JsonResponse
    {
        $results = $this->notificationService->processScheduledNotifications();

        return response()->json([
            'success' => true,
            'message' => 'Scheduled notifications processed',
            'data' => $results
        ]);
    }
}
