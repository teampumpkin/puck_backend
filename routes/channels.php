<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// Current public channel (working now)
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $chat = \App\Models\V4Chat::find($chatId);
    return $chat !== null;
});

// Future private channel (when you're ready)
Broadcast::channel('private-chat.{chatId}', function ($user, $chatId) {
    if (!$user) return false;

    $chat = \App\Models\V4Chat::find($chatId);
    if (!$chat) return false;

    // Check if user is participant
    if ($chat->is_group) {
        return \App\Models\V4ChatParticipant::where('chat_id', $chatId)
            ->where('user_id', $user->id)
            ->exists();
    }

    return $chat->user1 == $user->id || $chat->user2 == $user->id;
});

// Future presence channel (for user online/offline status)
Broadcast::channel('presence-chat.{chatId}', function ($user, $chatId) {
    if (!$user) return false;

    $chat = \App\Models\V4Chat::find($chatId);
    if (!$chat) return false;

    // Return user info for presence
    return [
        'id' => $user->id,
        'name' => $user->first_name . ' ' . $user->last_name,
    ];
});

// Private per-user channel for RPC-style responses (recent chats, notifications)
Broadcast::channel('private-user.{userId}', function ($user, $userId) {
    return $user && (int) $user->id === (int) $userId;
});
