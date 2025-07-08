<?php

namespace App\Services;

use App\Events\ConversationCreated;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ChatService
{
    /**
     * Create or get existing conversation between two users.
     */
    public function getOrCreateConversation(int $sellerId, int $buyerId, string $type = 'direct', ?string $title = null): Conversation
    {
        // Check if conversation already exists
        $conversation = Conversation::where(function ($query) use ($sellerId, $buyerId) {
            $query->where('seller_id', $sellerId)->where('buyer_id', $buyerId);
        })->orWhere(function ($query) use ($sellerId, $buyerId) {
            $query->where('seller_id', $buyerId)->where('buyer_id', $sellerId);
        })->where('type', $type)->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'seller_id'        => $sellerId,
                'buyer_id'         => $buyerId,
                'type'             => $type,
                'title'            => $title,
                'last_activity_at' => now(),
                'is_active'        => true,
            ]);

            // Broadcast new conversation created event
            broadcast(new ConversationCreated($conversation->load(['seller', 'buyer'])))->toOthers();
        }

        return $conversation;
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(
        int $conversationId,
        int $senderId,
        string $content,
        string $type = 'text'
    ): Message {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify sender is a participant
        if (! $conversation->isParticipant($senderId)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id'       => $senderId,
            'content'         => $content,
            'type'            => $type,
            'sent_at'         => now(),
            'is_read'         => false,
        ]);

        // Update conversation's last message and activity
        $conversation->update([
            'last_message_id'  => $message->id,
            'last_activity_at' => now(),
        ]);

        // Broadcast the message
        broadcast(new MessageSent($message));

        return $message->load(['sender', 'conversation']);
    }

    /**
     * Get conversations for a user with pagination.
     */
    public function getUserConversations(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Conversation::where('seller_id', $userId)
            ->orWhere('buyer_id', $userId)
            ->with(['lastMessage.sender', 'seller', 'buyer'])
            ->orderBy('last_activity_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get messages for a conversation with pagination.
     */
    public function getConversationMessages(int $conversationId, int $userId, int $perPage = 50): LengthAwarePaginator
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is a participant
        if (! $conversation->isParticipant($userId)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        return Message::where('conversation_id', $conversationId)
            ->with(['sender', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mark messages as read for a user in a conversation.
     */
    public function markMessagesAsRead(int $conversationId, int $userId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is a participant
        if (! $conversation->isParticipant($userId)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Get the messages that were marked as read to broadcast the event
        $readMessages = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', true)
            ->get();

        // Broadcast message read events
        foreach ($readMessages as $message) {
            broadcast(new MessageRead($message, $userId))->toOthers();
        }
    }

    /**
     * Get unread message count for a user.
     */
    public function getUnreadMessageCount(int $userId): int
    {
        return Message::whereHas('conversation', function ($query) use ($userId) {
            $query->where('seller_id', $userId)->orWhere('buyer_id', $userId);
        })->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Search conversations by title or participant name.
     */
    public function searchConversations(int $userId, string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Conversation::where(function ($q) use ($userId) {
            $q->where('seller_id', $userId)->orWhere('buyer_id', $userId);
        })->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
                ->orWhereHas('seller', function ($userQuery) use ($query) {
                    $userQuery->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%");
                })
                ->orWhereHas('buyer', function ($userQuery) use ($query) {
                    $userQuery->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%");
                });
        })->with(['lastMessage.sender', 'seller', 'buyer'])
            ->orderBy('last_activity_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Archive/deactivate a conversation.
     */
    public function archiveConversation(int $conversationId, int $userId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is a participant
        if (! $conversation->isParticipant($userId)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        $conversation->update(['is_active' => false]);
    }

    /**
     * Reactivate a conversation.
     */
    public function reactivateConversation(int $conversationId, int $userId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is a participant
        if (! $conversation->isParticipant($userId)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        $conversation->update(['is_active' => true]);
    }

    /**
     * Handle typing indicator broadcasting.
     */
    public function handleTyping(int $conversationId, int $userId, bool $isTyping = true): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is a participant
        if (! $conversation->isParticipant($userId)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        $user = User::findOrFail($userId);
        $userName = $user->first_name.' '.$user->last_name;

        // Broadcast typing indicator
        broadcast(new UserTyping($conversationId, $userId, $userName, $isTyping))->toOthers();
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(int $messageId, int $userId): void
    {
        $message = Message::findOrFail($messageId);
        $conversation = $message->conversation;

        // Verify user is a participant
        if (! $conversation->isParticipant($userId)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        // Don't mark own messages as read
        if ($message->sender_id === $userId) {
            return;
        }

        // Only mark as read if not already read
        if (! $message->is_read) {
            $message->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            // Broadcast message read event
            broadcast(new MessageRead($message, $userId))->toOthers();
        }
    }

    /**
     * Mark all messages in a conversation as read for a user.
     */
    public function markConversationAsRead(int $conversationId, int $userId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is a participant
        if (! $conversation->isParticipant($userId)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        // Mark all unread messages from other participants as read
        $messages = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->get();

        foreach ($messages as $message) {
            $message->update([
                'is_read' => true,
            ]);

            // Broadcast message read event
            broadcast(new MessageRead($message, $userId))->toOthers();
        }
    }
}
