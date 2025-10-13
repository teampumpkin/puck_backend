<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Api\BaseNotificationController;
use App\Http\Controllers\Controller;
use App\Models\V4User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Str;


class NotificationController extends Controller
{


    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // ==============================================
    // USER METHODS (Flutter App)
    // ==============================================

    /**
     * Get user notifications (Flutter App)
     */
    public function getUserNotifications(Request $request)
    {
        $this->middleware('auth:api');

        $params = $this->validateCommonParams($request);

        $limit = $params['limit'] ?? 20;
        $offset = $params['offset'] ?? 0;
        $type = $params['type'] ?? null;
        $unreadOnly = $params['unread_only'] ?? false;
        $withTrashed = $params['with_trashed'] ?? false;

        // Base query without reference relation for better performance
        if ($withTrashed) {
            $query = Notification::forUser(Auth::id())->withTrashed();
        } else {
            $query = Notification::forUser(Auth::id());
        }

        if ($type) {
            $query->ofType($type);
        }

        if ($unreadOnly) {
            $query->unread();
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($notification) {
                return $this->formatUserNotificationResponse($notification);
            });

        $unreadCount = $this->notificationService->getUnreadCount(Auth::user());

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'meta' => [
                    'total' => $notifications->count(),
                    'limit' => $limit,
                    'offset' => $offset,
                    'type' => $type,
                    'unread_only' => $unreadOnly,
                    'with_trashed' => $withTrashed,
                ]
            ]
        ]);
    }

    /**
     * Get user notification by ID (Flutter App)
     */
    public function getUserNotification($id)
    {


        $notification = Notification::with(['reference'])->findOrFail($id);

        // Check if notification belongs to user
        if ($notification->v4_user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Mark as read when viewing details
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'data' => $this->formatUserNotificationResponse($notification)
        ]);
    }

    /**
     * Get user trashed notifications (Flutter App)
     */
    public function getUserTrashedNotifications(Request $request)
    {


        $params = $this->validateCommonParams($request);

        $limit = $params['limit'] ?? 20;
        $offset = $params['offset'] ?? 0;

        $notifications = $this->notificationService->getTrashedNotifications(
            Auth::user(),
            $limit,
            $offset
        )->map(function ($notification) {
            return $this->formatUserNotificationResponse($notification);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'meta' => [
                    'total' => $notifications->count(),
                    'limit' => $limit,
                    'offset' => $offset,
                ]
            ]
        ]);
    }

    /**
     * Mark user notification as read (Flutter App)
     */
    public function markUserNotificationAsRead($id)
    {


        $notification = Notification::findOrFail($id);

        // Check if notification belongs to user
        if ($notification->v4_user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $this->formatUserNotificationResponse($notification)
        ]);
    }

    /**
     * Mark all user notifications as read (Flutter App)
     */
    public function markAllUserNotificationsAsRead()
    {


        $this->notificationService->markAllAsRead(Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Get user unread notifications count (Flutter App)
     */
    public function getUserUnreadCount()
    {


        $count = $this->notificationService->getUnreadCount(Auth::user());

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    /**
     * Delete user notification (Flutter App)
     */
    public function deleteUserNotification($id)
    {


        $notification = Notification::findOrFail($id);

        // Check if notification belongs to user
        if ($notification->v4_user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $this->notificationService->deleteNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification moved to trash'
        ]);
    }

    /**
     * Restore user notification (Flutter App)
     */
    public function restoreUserNotification($id)
    {


        $notification = Notification::withTrashed()->findOrFail($id);

        // Check if notification belongs to user and is trashed
        if ($notification->v4_user_id !== Auth::id() || !$notification->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or notification not found in trash'
            ], 403);
        }

        $this->notificationService->restoreNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification restored successfully',
            'data' => $this->formatUserNotificationResponse($notification)
        ]);
    }

    /**
     * Permanently delete user notification (Flutter App)
     */
    public function forceDeleteUserNotification($id)
    {


        $notification = Notification::withTrashed()->findOrFail($id);

        // Check if notification belongs to user
        if ($notification->v4_user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $this->notificationService->forceDeleteNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification permanently deleted'
        ]);
    }

    /**
     * Clear all user notifications (Flutter App)
     */
    public function clearAllUserNotifications()
    {


        $this->notificationService->clearAllNotifications(Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'All notifications moved to trash'
        ]);
    }

    /**
     * Restore all user trashed notifications (Flutter App)
     */
    public function restoreAllUserNotifications()
    {


        $this->notificationService->restoreAllNotifications(Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'All notifications restored from trash'
        ]);
    }

    /**
     * Empty user trash (Flutter App)
     */
    public function emptyUserTrash()
    {


        $this->notificationService->emptyTrash(Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'Trash emptied successfully'
        ]);
    }

    /**
     * Get user notification statistics (Flutter App)
     */
    public function getUserNotificationStatistics()
    {


        $user = Auth::user();

        $totalNotifications = Notification::forUser($user->id)->count();
        $unreadCount = $this->notificationService->getUnreadCount($user);
        $readCount = Notification::forUser($user->id)->read()->count();
        $trashedCount = Notification::forUser($user->id)->onlyTrashed()->count();

        // Count by type
        $typeCounts = Notification::forUser($user->id)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalNotifications,
                'unread' => $unreadCount,
                'read' => $readCount,
                'trashed' => $trashedCount,
                'by_type' => $typeCounts,
            ]
        ]);
    }

    // ==============================================
    // ADMIN METHODS (React Dashboard)
    // ==============================================

    /**
     * Get all notifications with filters (Admin)
     */
    public function getAdminNotifications(Request $request)
    {


        $params = $this->validateCommonParams($request);

        $limit = $params['limit'] ?? 20;
        $offset = $params['offset'] ?? 0;
        $type = $params['type'] ?? null;
        $unreadOnly = $params['unread_only'] ?? false;
        $withTrashed = $params['with_trashed'] ?? false;
        $userId = $params['user_id'] ?? null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $query = Notification::with(['user', 'reference']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($type) {
            $query->ofType($type);
        }

        if ($unreadOnly) {
            $query->unread();
        }

        if ($userId) {
            $query->forUser($userId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($notification) {
                return $this->formatAdminNotificationResponse($notification);
            });

        $totalCount = $query->count();

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'meta' => [
                    'total' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'type' => $type,
                    'unread_only' => $unreadOnly,
                    'with_trashed' => $withTrashed,
                    'user_id' => $userId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]
            ]
        ]);
    }

    /**
     * Get admin notification by ID (Admin)
     */
    public function getAdminNotification($id)
    {


        $notification = Notification::with(['user', 'reference'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatAdminNotificationResponse($notification)
        ]);
    }

    /**
     * Send notification to users (Admin)
     */
    public function sendAdminNotification(Request $request)
    {


        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'uuid|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string',
            'data' => 'sometimes|array',
            'redirect_url' => 'sometimes|string|nullable',
            'action_type' => 'sometimes|string|nullable',
        ]);

        $users = V4User::whereIn('id', $validated['user_ids'])->get();

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid users found'
            ], 422);
        }

        $results = $this->notificationService->sendToUsers(
            $users->toArray(),
            $validated['title'],
            $validated['message'],
            $validated['data'] ?? [],
            $validated['type'],
            $validated['redirect_url'] ?? null,
            $validated['action_type'] ?? null
        );

        $successCount = count(array_filter($results));
        $failedCount = count($results) - $successCount;

        return response()->json([
            'success' => true,
            'message' => "Notifications sent successfully. Success: {$successCount}, Failed: {$failedCount}",
            'data' => [
                'sent_count' => $successCount,
                'failed_count' => $failedCount,
                'results' => $results
            ]
        ]);
    }

    /**
     * Broadcast notification to all users (Admin)
     */
    public function broadcastAdminNotification(Request $request)
    {


        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string',
            'data' => 'sometimes|array',
            'redirect_url' => 'sometimes|string|nullable',
            'action_type' => 'sometimes|string|nullable',
            'user_type' => 'sometimes|string|in:all,active,inactive',
        ]);

        $query = V4User::query();

        if ($validated['user_type'] === 'active') {
            $query->where('last_active_at', '>=', now()->subDays(30));
        } elseif ($validated['user_type'] === 'inactive') {
            $query->where('last_active_at', '<', now()->subDays(30))->orWhereNull('last_active_at');
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No users found'
            ], 422);
        }

        $results = $this->notificationService->sendToUsers(
            $users->toArray(),
            $validated['title'],
            $validated['message'],
            $validated['data'] ?? [],
            $validated['type'],
            $validated['redirect_url'] ?? null,
            $validated['action_type'] ?? null
        );

        $successCount = count(array_filter($results));
        $failedCount = count($results) - $successCount;

        return response()->json([
            'success' => true,
            'message' => "Broadcast notification sent to {$successCount} users. Failed: {$failedCount}",
            'data' => [
                'total_users' => $users->count(),
                'sent_count' => $successCount,
                'failed_count' => $failedCount,
            ]
        ]);
    }

    /**
     * Delete admin notification (Admin)
     */
    public function deleteAdminNotification($id)
    {

        $notification = Notification::findOrFail($id);

        $this->notificationService->deleteNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification moved to trash'
        ]);
    }

    /**
     * Permanently delete admin notification (Admin)
     */
    public function forceDeleteAdminNotification($id)
    {


        $notification = Notification::withTrashed()->findOrFail($id);

        $this->notificationService->forceDeleteNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification permanently deleted'
        ]);
    }

    /**
     * Restore admin notification (Admin)
     */
    public function restoreAdminNotification($id)
    {

        $notification = Notification::withTrashed()->findOrFail($id);

        if (!$notification->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Notification is not in trash'
            ], 422);
        }

        $this->notificationService->restoreNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification restored successfully'
        ]);
    }

    /**
     * Get admin dashboard statistics (Admin)
     */
    public function getAdminDashboardStatistics()
    {


        $stats = [
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::unread()->count(),
            'read_notifications' => Notification::read()->count(),
            'trashed_notifications' => Notification::onlyTrashed()->count(),
            'today_notifications' => Notification::whereDate('created_at', today())->count(),
            'weekly_notifications' => Notification::where('created_at', '>=', now()->subWeek())->count(),
            'monthly_notifications' => Notification::where('created_at', '>=', now()->subMonth())->count(),
        ];

        // Notifications by type
        $typeStats = Notification::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        // Notifications by status
        $statusStats = Notification::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Daily notifications for the last 30 days
        $dailyStats = Notification::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $stats,
                'by_type' => $typeStats,
                'by_status' => $statusStats,
                'daily_trends' => $dailyStats,
            ]
        ]);
    }

    /**
     * Get user notification statistics (Admin)
     */
    public function getAdminUserStatistics($userId)
    {


        $user = V4User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $stats = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'total_notifications' => Notification::forUser($user->id)->count(),
            'unread_notifications' => Notification::forUser($user->id)->unread()->count(),
            'read_notifications' => Notification::forUser($user->id)->read()->count(),
            'trashed_notifications' => Notification::forUser($user->id)->onlyTrashed()->count(),
        ];

        // Notifications by type for this user
        $typeStats = Notification::forUser($user->id)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        $stats['by_type'] = $typeStats;

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Bulk operations (Admin)
     */
    public function adminBulkOperations(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:mark_read,mark_unread,delete,restore,force_delete',
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);

        $notifications = Notification::whereIn('id', $validated['notification_ids'])->get();

        $processed = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                switch ($validated['action']) {
                    case 'mark_read':
                        $notification->markAsRead();
                        break;
                    case 'mark_unread':
                        $notification->markAsUnread();
                        break;
                    case 'delete':
                        $notification->delete();
                        break;
                    case 'restore':
                        $notification->restore();
                        break;
                    case 'force_delete':
                        $notification->forceDelete();
                        break;
                }
                $processed++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk operation completed. Processed: {$processed}, Failed: {$failed}",
            'data' => [
                'processed' => $processed,
                'failed' => $failed
            ]
        ]);
    }

    // ==============================================
    // HELPER METHODS
    // ==============================================

    /**
     * Format notification response for user
     */
    protected function formatUserNotificationResponse(Notification $notification)
    {
        $formatted = [
            'id' => $notification->id,
            'v4_user_id' => $notification->v4_user_id,
            'title' => $notification->title,
            'message' => $notification->message,
            'icon' => $notification->icon,
            'icon_type' => $notification->icon_type,
            'icon_color' => $notification->icon_color,
            'icon_url' => $notification->getIconUrl(),
            'image_url' => $notification->getImageUrl(),
            'type' => $notification->type,
            'action_type' => $notification->action_type,
            'data' => $notification->data,
            'redirect_url' => $notification->redirect_url,
            'is_read' => $notification->isRead(),
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
            'updated_at' => $notification->updated_at,
            'deleted_at' => $notification->deleted_at,
            'is_trashed' => $notification->trashed(),
            // 'reference' => null,
        ];

        // Add reference data if exists
        // if ($notification->reference_type && $notification->reference_id) {
        //     $formatted['reference_info'] = [
        //         'type' => class_basename($notification->reference_type),
        //         'id' => $notification->reference_id,
        //     ];
        // } else {
        //     $formatted['reference_info'] = null;
        // }

        return $formatted;
    }

    /**
     * Format notification response for admin
     */
    protected function formatAdminNotificationResponse(Notification $notification)
    {
        $formatted = $this->formatUserNotificationResponse($notification);

        // Add user details for admin
        $formatted['user'] = $notification->user ? [
            'id' => $notification->user->id,
            'name' => $notification->user->name,
            'email' => $notification->user->email,
            'phone' => $notification->user->phone,
        ] : null;

        return $formatted;
    }

    /**
     * Get reference details based on type
     */
    protected function getReferenceDetails(Notification $notification)
    {
        if (!$notification->reference) {
            return null;
        }

        switch ($notification->reference_type) {
            case 'App\Models\Order':
                return [
                    'order_number' => $notification->reference->order_number ?? $notification->reference->id,
                    'status' => $notification->reference->status ?? 'unknown',
                    'total' => $notification->reference->total_amount ?? 0,
                ];

            case 'App\Models\Post':
                return [
                    'title' => $notification->reference->title ?? 'Untitled',
                    'slug' => $notification->reference->slug ?? '',
                ];

            case 'App\Models\Comment':
                return [
                    'content' => Str::limit($notification->reference->content ?? '', 100),
                    'post_id' => $notification->reference->post_id ?? null,
                ];

            default:
                return ['id' => $notification->reference_id];
        }
    }

    /**
     * Validate common notification parameters
     */
    protected function validateCommonParams(Request $request)
    {
        return $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0',
            'type' => 'sometimes|string',
            'unread_only' => 'sometimes|boolean',
            'with_trashed' => 'sometimes|boolean',
            'user_id' => 'sometimes|uuid',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);
    }
}
