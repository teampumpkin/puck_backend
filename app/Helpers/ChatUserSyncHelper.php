<?php

namespace App\Helpers;

use App\Models\V4User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Upserts a Laravel V4User into the chat microservice (Mongo).
 * The chat service's `PUT /user/update` does an upsert: it creates the
 * Mongo User doc if missing, otherwise patches it. This keeps both sides
 * in sync so conversation/create no longer 404s with "Users not found".
 */
class ChatUserSyncHelper
{
    public static function sync(V4User $user, ?string $token = null): bool
    {
        $baseUrl = config('services.chat.host');
        if (!$baseUrl) {
            Log::warning('CHAT_APP_HOST not configured; skipping chat user sync', ['user_id' => $user->id]);
            return false;
        }

        $payload = [
            'user_id' => (int) $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? null),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'date_of_birth' => $user->date_of_birth,
            'country' => $user->country,
            'state' => $user->state,
            'city' => $user->city,
            'zip' => $user->zip,
            'is_child' => (bool) $user->is_child,
            'parent_id' => $user->parent_id !== null ? (string) $user->parent_id : null,
            'username' => $user->username,
            'role' => $user->role,
            'age' => $user->age,
            'profile_photo' => $user->profile_photo,
        ];

        $payload = array_filter($payload, fn ($v) => $v !== null);
        $payload['user_id'] = (int) $user->id;

        try {
            $request = Http::timeout(10);
            if ($token) {
                $request = $request->withToken($token);
            }
            // Allow local dev to bypass an expired/self-signed chat-svc cert.
            if (filter_var(config('services.chat.verify_ssl', true), FILTER_VALIDATE_BOOLEAN) === false) {
                $request = $request->withoutVerifying();
            }

            $response = $request->put(rtrim($baseUrl, '/') . '/user/update', $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Chat user sync failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('Chat user sync exception: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            return false;
        }
    }
}
