<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('prepare_response')) {
    function prepare_response($code, $status, $message, array $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'code'    => $code,
            'status'  => $status,
            'message' => $message,
            'data'    => $data,

        ]);
    }
}

if (!function_exists('getV4UserFromToken')) {
    /**
     * Get authenticated V4 user from JWT token
     * This is the V4 equivalent of getUserIdAndType() for the V4 authentication system
     *
     * @param string $token Authorization header token
     * @return \App\Models\V4User|null
     */
    function getV4UserFromToken($token = null)
    {
        // If token is provided, try to authenticate using it
        if ($token) {
            // Remove 'Bearer ' prefix if present
            $token = str_replace('Bearer ', '', $token);

            try {
                // Set the token for the v4api guard
                Auth::guard('v4api')->setToken($token);
                return Auth::guard('v4api')->user();
            } catch (\Exception $e) {
                // If token authentication fails, return null
                return null;
            }
        }

        // Fallback to current authenticated user
        return Auth::guard('v4api')->user();
    }
}
