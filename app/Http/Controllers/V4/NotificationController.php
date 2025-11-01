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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

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
        try {
            $params = $this->validateCommonParams($request);

            $limit = $params['limit'] ?? 20;
            $offset = $params['offset'] ?? 0;
            $type = $params['type'] ?? null;
            $unreadOnly = $params['unread_only'] ?? false;
            $withTrashed = $params['with_trashed'] ?? false;

            // Base query without reference relation for better performance
            $user =  Auth::guard('v4api')->user();

            $query = Notification::forUser($user->Id);
            if ($withTrashed) {
                $query->withTrashed();
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
            $unreadCount = $this->notificationService->getUnreadCount($user);

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
        } catch (ValidationException $e) {
            // Handle validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            // Handle model not found exception (rare, unless using findOrFail)
            return response()->json([
                'success' => false,
                'message' => 'Notification data not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log and handle database errors
            Log::error('Database error during getUserNotifications operation.', [
                'user_id' => $user->Id,  // Logging the correct user ID
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            Log::error('Unexpected error during getUserNotifications operation.', [
                'user_id' => $user->Id,  // Logging the correct user ID
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get user notifications (Flutter App)
     */
    public function getChildNotifications(Request $request, $childId)
    {
        try {
            $child = V4User::findOrFail($childId);
            $params = $this->validateCommonParams($request);

            $limit = $params['limit'] ?? 20;
            $offset = $params['offset'] ?? 0;
            $type = $params['type'] ?? null;
            $unreadOnly = $params['unread_only'] ?? false;
            $withTrashed = $params['with_trashed'] ?? false;

            // Base query without reference relation for better performance
            $query = Notification::forUser($childId);
            if ($withTrashed) {
                $query->withTrashed();
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

            $unreadCount = $this->notificationService->getUnreadCount($child);

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
        } catch (ValidationException $e) {
            // Handle validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            // Handle case where child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log and handle database errors
            Log::error('Database error during getChildNotifications operation.', [
                'child_id' => $childId,  // Logging the correct child ID
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log and handle unexpected errors
            Log::error('Unexpected error during getChildNotifications operation.', [
                'child_id' => $childId,  // Logging the correct child ID
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get user notification by ID (Flutter App)
     */
    public function getUserNotification($id)
    {
        try {
            // Begin transaction (optional but consistent)
            DB::beginTransaction();
            $user =  Auth::guard('v4api')->user();

            $notification = Notification::with(['reference'])->findOrFail($id);
            // Check if the notification belongs to the authenticated user
            if ((int) $notification->v4_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this notification.'
                ], 403);
            }
            // Mark notification as read when viewed
            $notification->markAsRead();

            // Commit transaction
            DB::commit();

            // Return the formatted response
            return response()->json([
                'success' => true,
                'data' => $this->formatUserNotificationResponse($notification)
            ]);
        } catch (ModelNotFoundException $e) {
            // Notification not found
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log database errors
            Log::error('Database error during getUserNotification operation.', [
                'user_id' => $user->id ?? null,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log unexpected errors
            Log::error('Unexpected error during getUserNotification operation.', [
                'user_id' => $user->id ?? null,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Get user notification by ID (Flutter App)
     */
    public function getChildNotification($childId, $id)
    {
        try {
            // Begin database transaction (optional but consistent)
            DB::beginTransaction();

            // Ensure the child user exists
            $child = V4User::findOrFail($childId);

            $notification = Notification::with(['reference'])->findOrFail((int) $id);


            // Check if the notification belongs to the child
            if ((int) $notification->v4_user_id !== (int) $child->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this notification.'
                ], 403);
            }

            // Mark as read when viewing
            $notification->markAsRead();

            // Commit transaction
            DB::commit();

            // Return the formatted response
            return response()->json([
                'success' => true,
                'data' => $this->formatUserNotificationResponse($notification)
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where either the child or notification is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user or notification not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log and handle database errors
            Log::error('Database error during getChildNotification operation.', [
                'child_id' => $childId,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            // Rollback transaction in case of error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log and handle unexpected errors
            Log::error('Unexpected error during getChildNotification operation.', [
                'child_id' => $childId,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            // Rollback transaction in case of error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get user trashed notifications (Flutter App)
     */
    public function getUserTrashedNotifications(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Validate and retrieve parameters from request
            $params = $this->validateCommonParams($request);

            $limit = $params['limit'] ?? 20;
            $offset = $params['offset'] ?? 0;

            // Get trashed notifications using the service
            $notifications = $this->notificationService->getTrashedNotifications(
                $user,
                $limit,
                $offset
            )->map(function ($notification) {
                return $this->formatUserNotificationResponse($notification);
            });


            // Return the response with success data
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
        } catch (QueryException $e) {
            // Log database errors
            Log::error('Database error during getUserTrashedNotifications operation.', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log unexpected errors
            Log::error('Unexpected error during getUserTrashedNotifications operation.', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching trashed notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Get user trashed notifications (Flutter App)
     */
    public function getChildTrashedNotifications(Request $request, $childId)
    {
        try {
            // Find the child user or throw ModelNotFoundException
            $child = V4User::findOrFail($childId);


            // Validate and retrieve parameters from request
            $params = $this->validateCommonParams($request);

            $limit = $params['limit'] ?? 20;
            $offset = $params['offset'] ?? 0;

            // Get trashed notifications for the child
            $notifications = $this->notificationService->getTrashedNotifications(
                $child,
                $limit,
                $offset
            )->map(function ($notification) {
                return $this->formatUserNotificationResponse($notification);
            });

            // Return success response with notifications and metadata
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
        } catch (ModelNotFoundException $e) {
            // Handle case where child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log database errors
            Log::error('Database error during getChildTrashedNotifications operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log unexpected errors
            Log::error('Unexpected error during getChildTrashedNotifications operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching trashed notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Mark user notification as read (Flutter App)
     */
    public function markUserNotificationAsRead($id)
    {
        try {
            // Find the notification or throw ModelNotFoundException
            $notification = Notification::findOrFail($id);

            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Check if the notification belongs to the authenticated user
            if ((int) $notification->v4_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this notification.',
                ], 403);
            }

            // Check if the notification is already marked as read
            if ($notification->isRead()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification is already marked as read.',
                ]);
            }

            // Mark the notification as read
            $this->notificationService->markAsRead($notification);

            // Return success response with formatted notification data
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
                'data' => $this->formatUserNotificationResponse($notification)
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where notification is not found
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during markUserNotificationAsRead operation.', [
                'notification_id' => $id,
                'user_id' => Auth::guard('v4api')->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during markUserNotificationAsRead operation.', [
                'notification_id' => $id,
                'user_id' => Auth::guard('v4api')->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while marking the notification as read.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Mark user notification as read (Flutter App)
     */
    public function markChildNotificationAsRead($childId, $id)
    {
        try {
            // Find the notification or throw ModelNotFoundException
            $notification = Notification::findOrFail($id);

            // Find the child user or throw ModelNotFoundException
            $user = V4User::findOrFail($childId);

            // Check if the notification belongs to the child user
            if ((int) $notification->v4_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this notification.',
                ], 403);
            }

            // Check if the notification is already marked as read
            if ($notification->isRead()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification is already marked as read.',
                ]);
            }

            // Mark the notification as read
            $this->notificationService->markAsRead($notification);

            // Return success response with formatted notification data
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
                'data' => $this->formatUserNotificationResponse($notification)
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where notification or child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Notification or child user not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during markChildNotificationAsRead operation.', [
                'notification_id' => $id,
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during markChildNotificationAsRead operation.', [
                'notification_id' => $id,
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while marking the notification as read.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Mark user notification as unread (Flutter App)
     */
    public function markUserNotificationAsUnRead($id)
    {
        try {
            // Find the notification or throw ModelNotFoundException
            $notification = Notification::findOrFail($id);

            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Check if the notification belongs to the authenticated user
            if ((int) $notification->v4_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this notification.',
                ], 403);
            }

            // Check if the notification is already unread
            if (!$notification->isRead()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification is already unread.',
                ]);
            }

            // Mark the notification as unread
            $this->notificationService->markAsUnRead($notification);

            // Return success response with formatted notification data
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as unread.',
                'data' => $this->formatUserNotificationResponse($notification)
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where notification is not found
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during markUserNotificationAsUnRead operation.', [
                'notification_id' => $id,
                'user_id' => Auth::guard('v4api')->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during markUserNotificationAsUnRead operation.', [
                'notification_id' => $id,
                'user_id' => Auth::guard('v4api')->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while marking the notification as unread.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Mark child notification as unread (Flutter App)
     */
    public function markChildNotificationAsUnRead($childId, $id)
    {
        try {
            // Find the notification or throw ModelNotFoundException
            $notification = Notification::findOrFail($id);

            // Find the child user or throw ModelNotFoundException
            $user = V4User::findOrFail($childId);

            // Check if the notification belongs to the specified child user
            if ((int) $notification->v4_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this notification.',
                ], 403);
            }

            // Check if the notification is already unread
            if (!$notification->isRead()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification is already unread.',
                ]);
            }

            // Mark the notification as unread
            $this->notificationService->markAsUnRead($notification);

            // Return success response with formatted notification data
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as unread.',
                'data' => $this->formatUserNotificationResponse($notification)
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where notification or child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Notification or child user not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during markChildNotificationAsUnRead operation.', [
                'notification_id' => $id,
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during markChildNotificationAsUnRead operation.', [
                'notification_id' => $id,
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while marking the notification as unread.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Mark all user notifications as read (Flutter App)
     */
    public function markAllUserNotificationsAsRead()
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Mark all notifications for the user as read
            $this->notificationService->markAllAsRead($user);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.'
            ]);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during markAllUserNotificationsAsRead operation.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during markAllUserNotificationsAsRead operation.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while marking all notifications as read.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Mark all child notifications as read (Flutter App)
     */
    public function markAllChildNotificationsAsRead($childId)
    {
        try {
            // Find the child user or throw ModelNotFoundException
            $child = V4User::findOrFail($childId);

            // Mark all notifications for the child as read
            $this->notificationService->markAllAsRead($child);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during markAllChildNotificationsAsRead operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during markAllChildNotificationsAsRead operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while marking all notifications as read.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get user unread notifications count (Flutter App)
     */
    public function getUserUnreadCount()
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Get the unread notifications count for the user
            $count = $this->notificationService->getUnreadCount($user);

            // Return the unread count response
            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count
                ]
            ]);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during getUserUnreadCount operation.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while fetching unread notifications count.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during getUserUnreadCount operation.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching unread notifications count.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get child unread notifications count (Flutter App)
     */
    public function getChildUnreadCount($childId)
    {
        try {
            // Find the child user or throw ModelNotFoundException
            $child = V4User::findOrFail($childId);

            // Get the unread notifications count for the child
            $count = $this->notificationService->getUnreadCount($child);

            // Return the unread count response
            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during getChildUnreadCount operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while fetching unread notifications count.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during getChildUnreadCount operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching unread notifications count.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Delete user notification (Flutter App)
     */
    public function deleteUserNotification($id)
    {
        try {
            // Retrieve the notification by its ID
            $notification = Notification::findOrFail($id);

            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Check if the notification belongs to the authenticated user
            if ((int) $notification->v4_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Delete the notification (move to trash)
            $this->notificationService->deleteNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Notification moved to trash'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle notification not found
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during deleteUserNotification operation.', [
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while deleting the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during deleteUserNotification operation.', [
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while deleting the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Delete child notification (Flutter App)
     */
    public function deleteChildNotification($childId, $id)
    {
        try {
            // Retrieve the child user by their ID
            $user = V4User::findOrFail($childId);

            // Retrieve the notification by its ID
            $notification = Notification::findOrFail($id);

            // Check if the notification belongs to the child user
            if ((int) $notification->v4_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Delete the notification (move to trash)
            $this->notificationService->deleteNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Notification moved to trash'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where either the child user or notification is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user or notification not found.',
            ], 404);
        } catch (QueryException $e) {
            // Log any database query errors
            Log::error('Database error during deleteChildNotification operation.', [
                'child_id' => $childId,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while deleting the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during deleteChildNotification operation.', [
                'child_id' => $childId,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while deleting the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Restore user notification (Flutter App)
     */
    public function restoreUserNotification($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Retrieve the notification (including trashed ones)
            $notification = Notification::withTrashed()->findOrFail($id);

            // Check if the notification belongs to the authenticated user and is trashed
            if ((int) $notification->v4_user_id !== (int) $user->id || !$notification->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or notification not found in trash'
                ], 403);
            }

            // Restore the notification from trash
            $this->notificationService->restoreNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Notification restored successfully',
                'data' => $this->formatUserNotificationResponse($notification)
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle the case where the notification doesn't exist
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during restoreUserNotification operation.', [
                'user_id' => $user->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while restoring the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during restoreUserNotification operation.', [
                'user_id' => $user->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while restoring the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Restore child notification (Flutter App)
     */
    public function restoreChildNotification($childId, $id)
    {
        try {
            // Retrieve the child user by ID
            $user = V4User::findOrFail($childId);

            // Retrieve the notification (including trashed ones)
            $notification = Notification::withTrashed()->findOrFail($id);

            // Check if the notification belongs to the child user and is trashed
            if ((int) $notification->v4_user_id !== (int) $user->id || !$notification->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or notification not found in trash'
                ], 403);
            }

            // Restore the notification from trash
            $this->notificationService->restoreNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Notification restored successfully',
                'data' => $this->formatUserNotificationResponse($notification)
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where the notification or user is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user or notification not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during restoreChildNotification operation.', [
                'child_id' => $childId,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while restoring the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during restoreChildNotification operation.', [
                'child_id' => $childId,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while restoring the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Permanently delete user notification (Flutter App)
     */
    public function forceDeleteUserNotification($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Retrieve the notification, including trashed ones
            $notification = Notification::withTrashed()->findOrFail($id);

            // Check if the notification belongs to the authenticated user
            if ((int) $notification->v4_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Permanently delete the notification
            $this->notificationService->forceDeleteNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Notification permanently deleted'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where notification does not exist
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during forceDeleteUserNotification operation.', [
                'user_id' => $user->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while permanently deleting the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during forceDeleteUserNotification operation.', [
                'user_id' => $user->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while permanently deleting the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Permanently delete child notification (Flutter App)
     */
    public function forceDeleteChildNotification($childId, $id)
    {
        try {
            // Retrieve the child user by ID
            $user = V4User::findOrFail($childId);

            // Retrieve the notification, including trashed ones
            $notification = Notification::withTrashed()->findOrFail($id);

            // Check if the notification belongs to the child user
            if ((int) $notification->v4_user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Permanently delete the notification
            $this->notificationService->forceDeleteNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Notification permanently deleted'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where notification or child user does not exist
            return response()->json([
                'success' => false,
                'message' => 'Child user or notification not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during forceDeleteChildNotification operation.', [
                'child_id' => $childId,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while permanently deleting the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during forceDeleteChildNotification operation.', [
                'child_id' => $childId,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while permanently deleting the notification.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Clear all user notifications (Flutter App)
     */
    public function clearAllUserNotifications()
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Clear all notifications for the user
            $this->notificationService->clearAllNotifications($user);

            return response()->json([
                'success' => true,
                'message' => 'All notifications moved to trash'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where user is not found
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during clearAllUserNotifications operation.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while clearing all notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during clearAllUserNotifications operation.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while clearing all notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Clear all child notifications (Flutter App)
     */
    public function clearAllChildNotifications($childId)
    {
        try {
            // Retrieve the child user by ID
            $user = V4User::findOrFail($childId);

            // Clear all notifications for the child user
            $this->notificationService->clearAllNotifications($user);

            return response()->json([
                'success' => true,
                'message' => 'All notifications moved to trash'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during clearAllChildNotifications operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while clearing all notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during clearAllChildNotifications operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while clearing all notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Restore all user trashed notifications (Flutter App)
     */
    public function restoreAllUserNotifications()
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Restore all notifications for the user
            $this->notificationService->restoreAllNotifications($user);

            return response()->json([
                'success' => true,
                'message' => 'All notifications restored from trash'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where user is not found
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during restoreAllUserNotifications operation.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while restoring all notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during restoreAllUserNotifications operation.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while restoring all notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Restore all child notifications from trash (Flutter App)
     */
    public function restoreAllChildNotifications($childId)
    {
        try {
            // Retrieve the child user by ID
            $user = V4User::findOrFail($childId);

            // Restore all notifications for the child user
            $this->notificationService->restoreAllNotifications($user);

            return response()->json([
                'success' => true,
                'message' => 'All notifications restored from trash'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during restoreAllChildNotifications operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while restoring all notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during restoreAllChildNotifications operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while restoring all notifications.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Empty user trash (Flutter App)
     */
    public function emptyUserTrash()
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Empty the trash for the user
            $this->notificationService->emptyTrash($user);

            return response()->json([
                'success' => true,
                'message' => 'Trash emptied successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where user is not found
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during emptyUserTrash operation.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while emptying trash.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during emptyUserTrash operation.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while emptying trash.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Empty child trash (Flutter App)
     */
    public function emptyChildTrash($childId)
    {
        try {
            // Retrieve the child user by ID
            $user = V4User::findOrFail($childId);

            // Empty the trash for the child user
            $this->notificationService->emptyTrash($user);

            return response()->json([
                'success' => true,
                'message' => 'Trash emptied successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during emptyChildTrash operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while emptying trash.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during emptyChildTrash operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while emptying trash.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get user notification statistics (Flutter App)
     */
    public function getUserNotificationStatistics()
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('v4api')->user();

            // Fetch notification statistics
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
        } catch (ModelNotFoundException $e) {
            // Handle case where user is not found
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during getUserNotificationStatistics operation.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while fetching statistics.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during getUserNotificationStatistics operation.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching statistics.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get child notification statistics (Flutter App)
     */
    public function getChildNotificationStatistics($childId)
    {
        try {
            // Retrieve the child user by ID
            $user = V4User::findOrFail($childId);

            // Fetch notification statistics
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
        } catch (ModelNotFoundException $e) {
            // Handle case where child user is not found
            return response()->json([
                'success' => false,
                'message' => 'Child user not found.'
            ], 404);
        } catch (QueryException $e) {
            // Log any database errors
            Log::error('Database error during getChildNotificationStatistics operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while fetching statistics.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('Unexpected error during getChildNotificationStatistics operation.', [
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching statistics.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
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


        $user = V4User::findOrFail($userId);

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
