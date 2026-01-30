<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4NotificationPreference;
use App\Models\V4User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Contracts\ErrorTrackerInterface;

class V4NotificationPreferenceController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }


    public function getPreferences(Request $request, $userId = null): JsonResponse
    {
        try {
            DB::beginTransaction();

            if ($userId) {
                $user = V4User::findOrFail($userId);
            } else {
                $authUserId = Auth::guard('v4api')->id();
                $user       = V4User::findOrFail($authUserId);
            }

            $preferences = V4NotificationPreference::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'pause_all' => false,
                    'messages'  => true,
                    'followers' => true,
                    'following' => true,
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Notification preferences fetched successfully.',
                'data'    => $preferences,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'User not found.',
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Get Preferences Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch notification preferences.',
            ], 500);
        }
    }

    public function updatePreferences(Request $request, $userId = null): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'pause_all' => 'nullable|boolean',
                'messages'  => 'nullable|boolean',
                'followers' => 'nullable|boolean',
                'following' => 'nullable|boolean',
            ]);

            if ($userId) {
                $user = V4User::findOrFail($userId);
            } else {
                $authUserId = Auth::guard('v4api')->id();
                $user       = V4User::findOrFail($authUserId);
            }

            $preferences = V4NotificationPreference::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'pause_all' => false,
                    'messages'  => true,
                    'followers' => true,
                    'following' => true,
                ]
            );

            if (! empty($validated['pause_all']) && $validated['pause_all']) {
                $validated['messages']  = false;
                $validated['followers'] = false;
                $validated['following'] = false;
            }

            $preferences->update($validated);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Notification preferences updated successfully.',
                'data'    => $preferences->fresh(),
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error('DB Update Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Database error occurred.',
            ], 500);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'User not found.',
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Update Preferences Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Failed to update notification preferences.',
            ], 500);
        }
    }

    public function deletePreferences(): JsonResponse
    {
        try {
            DB::beginTransaction();

            $userId = Auth::guard('v4api')->id();
            $user   = V4User::findOrFail($userId);

            $preferences = $user->notificationPreferences;

            if (! $preferences) {
                throw new ModelNotFoundException('Preferences not found.');
            }

            $preferences->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Notification preferences deleted successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Preferences Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete notification preferences.',
            ], 500);
        }
    }

    public function restorePreferences(): JsonResponse
    {
        try {
            DB::beginTransaction();

            $userId = Auth::guard('v4api')->id();
            $user   = V4User::findOrFail($userId);

            $preferences = V4NotificationPreference::withTrashed()
                ->where('user_id', $user->id)
                ->first();

            if (! $preferences) {
                throw new ModelNotFoundException('Preferences not found.');
            }

            if ($preferences->trashed()) {
                $preferences->restore();
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Notification preferences restored successfully.',
                'data'    => $preferences->fresh(),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            

            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Restore Preferences Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Failed to restore notification preferences.',
            ], 500);
        }
    }
}
