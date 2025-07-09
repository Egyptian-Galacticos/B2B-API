<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Private user channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat conversation channels
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    try {
        $conversation = App\Models\Conversation::find($conversationId);

        if (! $conversation) {
            \Illuminate\Support\Facades\Log::warning('Conversation not found for channel authorization', ['conversation_id' => $conversationId, 'user_id' => $user->id]);

            return false;
        }

        $isParticipant = $conversation->isParticipant($user->id);

        if (! $isParticipant) {
            \Illuminate\Support\Facades\Log::warning('User not authorized for conversation', ['conversation_id' => $conversationId, 'user_id' => $user->id]);
        }

        return $isParticipant;
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Error in conversation channel authorization', ['error' => $e->getMessage(), 'conversation_id' => $conversationId, 'user_id' => $user->id]);

        return false;
    }
});

// Private user notifications
Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    try {
        return (int) $user->id === (int) $userId;
    } catch (\Exception $e) {
        return false;
    }
});

// Company-wide notifications (for company members)
Broadcast::channel('company.{companyId}.notifications', function ($user, $companyId) {
    return $user->company && (int) $user->company->id === (int) $companyId;
});

// Product updates (for sellers)
Broadcast::channel('seller.{sellerId}.products', function ($user, $sellerId) {
    return (int) $user->id === (int) $sellerId && $user->hasRole('seller');
});

// User typing indicator in conversation
Broadcast::channel('chat.conversation.{conversationId}.typing', function ($user, $conversationId) {
    // Check if user is part of this conversation
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});

// User presence channel
Broadcast::channel('user.{userId}.presence', function ($user, $userId) {
    try {
        return (int) $user->id === (int) $userId;
    } catch (\Exception $e) {
        return false;
    }
});

// Online users presence channel
Broadcast::channel('online-users', function ($user) {
    try {
        // Return user info for presence channel
        return [
            'id'         => $user->id,
            'name'       => $user->first_name.' '.$user->last_name,
            'email'      => $user->email,
            'avatar_url' => $user->company->getFirstMediaUrl('logo'),
        ];
    } catch (\Exception $e) {
        return false;
    }
});
