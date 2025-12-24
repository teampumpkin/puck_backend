<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\V4User;
use App\Helpers\PushNotificationHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    protected $pushNotificationHelper;

    public function __construct(PushNotificationHelper $pushNotificationHelper)
    {
        $this->pushNotificationHelper = $pushNotificationHelper;
    }

    /**
     * Send notification to a single user with reference and redirection
     */
    public function sendToUser(
        V4User $user,
        string $title,
        string $message,
        array $data = [],
        string $type = 'general',
        ?string $redirectUrl = null,
        ?string $actionType = null,
        ?Model $reference = null,
        ?string $icon = null,
        ?string $iconType = null,
        ?string $iconColor = null,
        ?string $imageUrl = null
    ) {
        try {
            $notificationData = [
                'v4_user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data,
                'sent_via' => 'database',
                'status' => 'sent',
                'redirect_url' => $redirectUrl,
                'action_type' => $actionType,
            ];

            // Add icon fields if provided
            if ($icon !== null) {
                $notificationData['icon'] = $icon;
                $notificationData['icon_type'] = $iconType ?: Notification::ICON_TYPE_DEFAULT;
                $notificationData['icon_color'] = $iconColor;
            }

            // Add image URL if provided
            if ($imageUrl !== null) {
                $notificationData['image_url'] = $imageUrl;
            }


            // Add reference if provided
            if ($reference) {
                $notificationData['reference_type'] = get_class($reference);
                $notificationData['reference_id'] = $reference->id;
            }

            // Store in database
            $notification = Notification::create($notificationData);

            // If no icon was provided, set default icon based on type
            if ($icon === null) {
                $notification->setDefaultIcon();
            }

            // Prepare push notification data
            $pushData = array_merge($data, [
                'notification_id' => $notification->id,
                'type' => $type,
                'action_type' => $actionType ?: $type,
                'redirect_url' => $notification->redirect_url,
                'reference_type' => $notification->reference_type,
                'reference_id' => $notification->reference_id,
                'icon' => $notification->icon,
                'icon_type' => $notification->icon_type,
                'icon_color' => $notification->icon_color,
                'image_url' => $notification->image_url,
            ]);

            Log::info(
                "sendToToken",
                [
                    $user->fcm_token,
                    $title,
                    $message,
                    $pushData
                ]
            );

            // Send push notification if user has FCM token
            if ($user->fcm_token) {
                $this->pushNotificationHelper->sendToToken(
                    $user->fcm_token,
                    $title,
                    $message,
                    $pushData
                );
            }

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to send notification to user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification with emoji icon
     */
    public function sendToUserWithEmoji(
        V4User $user,
        string $title,
        string $message,
        string $emojiIcon,
        array $data = [],
        string $type = 'general',
        ?string $redirectUrl = null,
        ?string $actionType = null,
        ?Model $reference = null
    ) {
        return $this->sendToUser(
            $user,
            $title,
            $message,
            $data,
            $type,
            $redirectUrl,
            $actionType,
            $reference,
            $emojiIcon,
            Notification::ICON_TYPE_DEFAULT
        );
    }

    /**
     * Send notification with material icon
     */
    public function sendToUserWithMaterialIcon(
        V4User $user,
        string $title,
        string $message,
        string $materialIcon,
        ?string $iconColor = null,
        array $data = [],
        string $type = 'general',
        ?string $redirectUrl = null,
        ?string $actionType = null,
        ?Model $reference = null
    ) {
        return $this->sendToUser(
            $user,
            $title,
            $message,
            $data,
            $type,
            $redirectUrl,
            $actionType,
            $reference,
            $materialIcon,
            Notification::ICON_TYPE_MATERIAL,
            $iconColor
        );
    }

    /**
     * Send notification with URL icon
     */
    public function sendToUserWithUrlIcon(
        V4User $user,
        string $title,
        string $message,
        string $iconUrl,
        ?string $iconColor = null,
        array $data = [],
        string $type = 'general',
        ?string $redirectUrl = null,
        ?string $actionType = null,
        ?Model $reference = null
    ) {
        return $this->sendToUser(
            $user,
            $title,
            $message,
            $data,
            $type,
            $redirectUrl,
            $actionType,
            $reference,
            $iconUrl,
            Notification::ICON_TYPE_URL,
            $iconColor
        );
    }

    /**
     * Send notification with image
     */
    public function sendToUserWithImage(
        V4User $user,
        string $title,
        string $message,
        string $imageUrl,
        array $data = [],
        string $type = 'general',
        ?string $redirectUrl = null,
        ?string $actionType = null,
        ?Model $reference = null,
        ?string $icon = null,
        ?string $iconType = null
    ) {
        return $this->sendToUser(
            $user,
            $title,
            $message,
            $data,
            $type,
            $redirectUrl,
            $actionType,
            $reference,
            $icon,
            $iconType,
            null,
            $imageUrl
        );
    }


    /**
     * Send notification to multiple users
     */
    public function sendToUsers(
        array $users,
        string $title,
        string $message,
        array $data = [],
        string $type = 'general',
        ?string $redirectUrl = null,
        ?string $actionType = null,
        ?Model $reference = null
    ) {
        $results = [];

        foreach ($users as $user) {
            $results[] = $this->sendToUser(
                $user,
                $title,
                $message,
                $data,
                $type,
                $redirectUrl,
                $actionType,
                $reference
            );
        }

        return $results;
    }

    /**
     * Send order-related notification
     */
    public function sendOrderNotification(
        V4User $user,
        string $title,
        string $message,
        $order,
        string $type = 'order_updated'
    ) {
        return $this->sendToUser(
            $user,
            $title,
            $message,
            [
                'order_number' => $order->order_number ?? $order->id,
                'order_status' => $order->status ?? 'unknown',
                'amount' => $order->total_amount ?? 0,
            ],
            $type,
            null,
            'order_action',
            $order
        );
    }

    /**
     * Send post-related notification
     */
    public function sendPostNotification(
        V4User $user,
        string $title,
        string $message,
        $post,
        string $type = 'post_created'
    ) {
        return $this->sendToUser(
            $user,
            $title,
            $message,
            [
                'post_title' => $post->title ?? 'Untitled',
                'post_slug' => $post->slug ?? '',
            ],
            $type,
            null,
            'post_action',
            $post
        );
    }

    /**
     * Send comment-related notification
     */
    public function sendCommentNotification(
        V4User $user,
        string $title,
        string $message,
        $comment,
        string $type = 'comment_added'
    ) {
        return $this->sendToUser(
            $user,
            $title,
            $message,
            [
                'comment_content' => Str::limit($comment->content ?? '', 100),
                'post_id' => $comment->post_id ?? null,
            ],
            $type,
            null,
            'comment_action',
            $comment
        );
    }

    /**
     * Get user notifications (excluding soft deleted)
     */
    public function getUserNotifications(V4User $user, $limit = 20, $offset = 0)
    {
        return Notification::forUser($user->id)
            ->with(['user', 'reference'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Get notifications with relations (excluding soft deleted)
     */
    public function getUserNotificationsWithRelations(V4User $user, $limit = 20, $offset = 0)
    {
        return Notification::forUser($user->id)
            ->with(['reference'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($notification) {
                return $this->formatNotificationWithRelations($notification);
            });
    }

    /**
     * Get user notifications including trashed
     */
    public function getUserNotificationsWithTrashed(V4User $user, $limit = 20, $offset = 0)
    {
        return Notification::forUser($user->id)
            ->withTrashed()
            ->with(['reference'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Get only trashed notifications for user
     */
    public function getTrashedNotifications(V4User $user, $limit = 20, $offset = 0)
    {
        return Notification::forUser($user->id)
            ->onlyTrashed()
            ->with(['reference'])
            ->orderBy('deleted_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Format notification with relations
     */
    protected function formatNotificationWithRelations(Notification $notification)
    {
        $formatted = $notification->toArray();

        // Add reference data based on type
        if ($notification->reference) {
            $formatted['reference_data'] = $this->getReferenceData($notification);
        }

        return $formatted;
    }

    /**
     * Get reference data based on reference type
     */
    protected function getReferenceData(Notification $notification)
    {
        switch ($notification->reference_type) {
            case 'App\Models\Order':
                return [
                    'order_number' => $notification->reference->order_number ?? $notification->reference->id,
                    'status' => $notification->reference->status ?? 'unknown',
                    'total_amount' => $notification->reference->total_amount ?? 0,
                ];

            case 'App\Models\Post':
                return [
                    'title' => $notification->reference->title ?? 'Untitled',
                    'slug' => $notification->reference->slug ?? '',
                    'excerpt' => Str::limit($notification->reference->content ?? '', 100),
                ];

            case 'App\Models\Comment':
                return [
                    'content' => Str::limit($notification->reference->content ?? '', 100),
                    'post_id' => $notification->reference->post_id ?? null,
                ];

            default:
                return null;
        }
    }

    /**
     * Get notifications by reference
     */
    public function getNotificationsByReference($referenceType, $referenceId)
    {
        return Notification::withReference($referenceType, $referenceId)
            ->with('user')
            ->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        return $notification->markAsRead();
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnRead(Notification $notification)
    {
        return $notification->markAsUnRead();
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(V4User $user)
    {
        return Notification::forUser($user->id)->unread()->update(['read_at' => now()]);
    }

    /**
     * Get unread notifications count (excluding soft deleted)
     */
    public function getUnreadCount(V4User $user)
    {
        return Notification::forUser($user->id)->unread()->count();
    }

    /**
     * Soft delete notification
     */
    public function deleteNotification(Notification $notification)
    {
        return $notification->softDelete();
    }

    /**
     * Restore soft deleted notification
     */
    public function restoreNotification(Notification $notification)
    {
        return $notification->restoreNotification();
    }

    /**
     * Permanently delete notification
     */
    public function forceDeleteNotification(Notification $notification)
    {
        return $notification->forceDelete();
    }

    /**
     * Clear all notifications for user (soft delete)
     */
    public function clearAllNotifications(V4User $user)
    {
        return Notification::forUser($user->id)->delete();
    }

    /**
     * Permanently clear all notifications for user
     */
    public function forceClearAllNotifications(V4User $user)
    {
        return Notification::forUser($user->id)->forceDelete();
    }

    /**
     * Restore all trashed notifications for user
     */
    public function restoreAllNotifications(V4User $user)
    {
        return Notification::forUser($user->id)->onlyTrashed()->restore();
    }

    /**
     * Empty trash for user (permanently delete soft deleted notifications)
     */
    public function emptyTrash(V4User $user)
    {
        return Notification::forUser($user->id)->onlyTrashed()->forceDelete();
    }
}
