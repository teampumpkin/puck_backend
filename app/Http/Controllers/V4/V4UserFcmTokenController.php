<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4UserFcmToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;

class V4UserFcmTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fcm_token'   => 'required|string',
                'device_type' => 'nullable|string',
                'device_id'   => 'nullable|string',
            ]);

            $user = Auth::guard('v4api')->user();

            if ($validated['device_id'] ?? null) {
                $token = V4UserFcmToken::updateOrCreate(
                    [
                        'user_id'   => $user->id,
                        'device_id' => $validated['device_id'],
                    ],
                    [
                        'fcm_token'   => $validated['fcm_token'],
                        'device_type' => $validated['device_type'] ?? null,
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'FCM token updated for this device.',
                    'data'    => $token,
                ]);
            }

            $token = V4UserFcmToken::firstOrCreate(
                [
                    'user_id'   => $user->id,
                    'fcm_token' => $validated['fcm_token'],
                ],
                [
                    'device_type' => $validated['device_type'] ?? null,
                    'device_id'   => $validated['device_id'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'FCM token saved successfully.',
                'data'    => $token,
            ]);
        } catch (ValidationException $e) {
            Log::warning("FCM token validation failed", [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {

            Log::error("FCM token save error", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while saving the token.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fcm_token' => 'required|string',
            ]);

            $userId = Auth::guard('v4api')->id();

            $deleted = V4UserFcmToken::where('user_id', $userId)
                ->where('fcm_token', $validated['fcm_token'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => $deleted
                    ? 'FCM token removed.'
                    : 'Token not found or already removed.',
            ]);
        } catch (ValidationException $e) {

            Log::warning("FCM token delete validation failed", [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {

            Log::error("FCM token delete error", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while deleting the token.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
