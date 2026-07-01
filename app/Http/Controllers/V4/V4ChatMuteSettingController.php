<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4ChatMuteSetting;
use App\Models\V4User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Contracts\ErrorTrackerInterface;

class V4ChatMuteSettingController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }

    public function mute(Request $request, $chatId): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $request->validate([
                'duration' => 'required|integer|min:0', // Allow for 0 to represent infinite mute
            ]);

            $mutedUntil = $request->duration === 0 ? null : now()->addMinutes($request->duration);

            $muteSetting = V4ChatMuteSetting::updateOrCreate(
                ['user_id' => $user->id, 'chat_id' => $chatId],
                [
                    'duration'    => $request->duration,
                    'muted_until' => $mutedUntil,
                    'active'      => true,
                ]
            );
            $token   = $request->bearerToken();
            $baseUrl = config('services.chat.host');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ])->post($baseUrl . '/conversation/mute', [
                'userId'         => $user->id,
                'conversationId' => $chatId,
                'mutedUntil'     => $mutedUntil ? $mutedUntil->toIso8601String() : 'infinity',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mute successfully',
                'data'    => $muteSetting,
            ], 200);
        } catch (ValidationException $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User or chat not found',
            ], 404);
        } catch (QueryException $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error'   => $e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function unmute(Request $request, $chatId): JsonResponse
    {
        try {
            $user        = Auth::guard('v4api')->user();
            $muteSetting = V4ChatMuteSetting::where('chat_id', $chatId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $muteSetting->update(['active' => false]);

            $token   = $request->bearerToken();
            $baseUrl = config('services.chat.host');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ])->post($baseUrl . '/conversation/unmute', [
                'userId'         => $user->id,
                'conversationId' => $chatId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Unmuted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Mute setting not found',
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error'   => $e->getMessage(),
            ], 500);
        } catch (Exception $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserMuteSettings($chatId = null): JsonResponse
    {
        try {
            $authUser = Auth::guard('v4api')->user();

            $user = V4User::findOrFail($authUser->id);

            if ($chatId !== null) {
                $muteSetting = $user->muteSettings()
                    ->where('chat_id', $chatId)
                    ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Mute setting retrieved successfully',
                    'data'    => $muteSetting,
                ], 200);
            }

            $muteSettings = $user->muteSettings()->get();
            return response()->json([
                'success' => true,
                'message' => 'Mute settings retrieved successfully',
                'data'    => $muteSettings,
            ], 200);
        } catch (ModelNotFoundException $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
