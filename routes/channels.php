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
Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is part of this conversation
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});

// Private user notifications
Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {

    return $user && (int) $user->id === (int) $userId;
});

// Company-wide notifications (for company members)
Broadcast::channel('company.{companyId}.notifications', function ($user, $companyId) {
    return $user->company && (int) $user->company->id === (int) $companyId;
});

// Product updates (for sellers)
Broadcast::channel('seller.{sellerId}.products', function ($user, $sellerId) {
    return (int) $user->id === (int) $sellerId && $user->hasRole('seller');
});
