<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendNotificationRequest;
use App\Http\Requests\UpdateNotificationTokensRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Providers\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Exception;

class NotificationController extends Controller
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $query = Notification::with(['sender', 'user', 'stateInfo'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('state')) {
            $query->byState($request->state);
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
     * Create and send notification
     */
    public function store(SendNotificationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $senderId = Auth::id();

            switch ($data['type']) {
                case 'admin':
                    $this->authorize('sendAdminNotification', Notification::class);
                    $result = $this->notificationService->sendAdminNotification(
                        $data['title'],
                        $data['message'],
                        $senderId,
                        $data['metadata'] ?? null,
                        $data['scheduled_at'] ?? null
                    );
                    break;

                case 'state':
                    $this->authorize('sendStateNotification', Notification::class);
                    $result = $this->notificationService->sendStateNotification(
                        $data['title'],
                        $data['message'],
                        $data['state'],
                        $senderId,
                        $data['metadata'] ?? null,
                        $data['scheduled_at'] ?? null
                    );
                    break;

                case 'personal':
                    $this->authorize('sendPersonalNotification', Notification::class);
                    $result = $this->notificationService->sendPersonalNotification(
                        $data['title'],
                        $data['message'],
                        $data['user_id'],
                        $senderId,
                        $data['metadata'] ?? null,
                        $data['scheduled_at'] ?? null
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
     * Get specific notification details
     */
    public function show($id): JsonResponse
    {
        $notification = Notification::with(['sender', 'user', 'stateInfo', 'recipients.user'])
            ->findOrFail($id);

        $this->authorize('view', $notification);

        $stats = $this->notificationService->getNotificationStats($id);

        return response()->json([
            'success' => true,
            'data' => new NotificationResource($notification),
            'stats' => $stats
        ]);
    }

    /**
     * Update notification (mainly for scheduling)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $notification = Notification::findOrFail($id);
        $this->authorize('update', $notification);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
            'scheduled_at' => 'sometimes|nullable|date|after:now',
            'metadata' => 'sometimes|array',
        ]);

        // Only allow updates if notification is in draft status
        if ($notification->status !== Notification::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft notifications can be updated'
            ], 400);
        }

        $notification->update($request->only(['title', 'message', 'scheduled_at', 'metadata']));

        return response()->json([
            'success' => true,
            'message' => 'Notification updated successfully',
            'data' => new NotificationResource($notification)
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy($id): JsonResponse
    {
        $notification = Notification::findOrFail($id);
        $this->authorize('delete', $notification);

        // Only allow deletion if notification is in draft status
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
     * Get user's notifications
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
     * Mark notification as read
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
     * Update user's notification tokens
     */
    public function updateTokens(UpdateNotificationTokensRequest $request): JsonResponse
    {
        $data = $request->validated();
        $userId = Auth::id();

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
        $request->validate([
            'push_enabled' => 'required|boolean',
            'admin_notifications' => 'boolean',
            'state_notifications' => 'boolean',
            'personal_notifications' => 'boolean',
        ]);

        $userId = Auth::id();
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
     * Process scheduled notifications (for cron job)
     */
    public function processScheduled(): JsonResponse
    {
        $this->authorize('processScheduledNotifications', Notification::class);

        $results = $this->notificationService->processScheduledNotifications();

        return response()->json([
            'success' => true,
            'message' => 'Scheduled notifications processed',
            'data' => $results
        ]);
    }
}
