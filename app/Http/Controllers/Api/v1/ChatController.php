<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Requests\Chat\StartConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private ChatService $chatService
    ) {
        // Middleware will be applied in routes
    }

    /**
     * Get user's conversations.
     */
    public function conversations(Request $request): JsonResponse
    {
        $conversations = $this->chatService->getUserConversations(
            auth()->user()->id,
            $request->get('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data'    => ConversationResource::collection($conversations->items()),
            'meta'    => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'per_page'     => $conversations->perPage(),
                'total'        => $conversations->total(),
            ],
        ]);
    }

    /**
     * Start a new conversation or get existing one.
     */
    public function startConversation(StartConversationRequest $request): JsonResponse
    {
        $userId = auth()->user()->id;
        $otherUserId = $request->validated('user_id');

        // Determine who is seller and who is buyer based on user roles
        $user = auth()->user();
        $otherUser = \App\Models\User::findOrFail($otherUserId);

        if ($user->isSeller() && $otherUser->isBuyer()) {
            $sellerId = $userId;
            $buyerId = $otherUserId;
        } elseif ($user->isBuyer() && $otherUser->isSeller()) {
            $sellerId = $otherUserId;
            $buyerId = $userId;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Conversations can only be started between buyers and sellers.',
            ], 400);
        }

        $conversation = $this->chatService->getOrCreateConversation(
            $sellerId,
            $buyerId,
            $request->validated('type', 'direct'),
            $request->validated('title')
        );

        return response()->json([
            'success' => true,
            'data'    => new ConversationResource($conversation->load(['seller', 'buyer', 'lastMessage.sender'])),
        ]);
    }

    /**
     * Get messages for a conversation.
     */
    public function messages(Request $request, int $conversationId): JsonResponse
    {
        try {
            $messages = $this->chatService->getConversationMessages(
                $conversationId,
                auth()->user()->id,
                $request->get('per_page', 50)
            );

            return response()->json([
                'success' => true,
                'data'    => MessageResource::collection($messages->items()),
                'meta'    => [
                    'current_page' => $messages->currentPage(),
                    'last_page'    => $messages->lastPage(),
                    'per_page'     => $messages->perPage(),
                    'total'        => $messages->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        try {
            $message = $this->chatService->sendMessage(
                $conversationId,
                auth()->user()->id,
                $request->validated('content'),
                $request->validated('type', 'text')
            );

            return response()->json([
                'success' => true,
                'data'    => new MessageResource($message),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Mark messages as read in a conversation.
     */
    public function markAsRead(Request $request, int $conversationId): JsonResponse
    {
        try {
            $this->chatService->markMessagesAsRead($conversationId, auth()->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get unread message count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->chatService->getUnreadMessageCount(auth()->user()->id);

        return response()->json([
            'success' => true,
            'data'    => ['count' => $count],
        ]);
    }

    /**
     * Search conversations.
     */
    public function searchConversations(Request $request): JsonResponse
    {
        $request->validate([
            'query'    => 'required|string|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $conversations = $this->chatService->searchConversations(
            auth()->user()->id,
            $request->get('query'),
            $request->get('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data'    => ConversationResource::collection($conversations->items()),
            'meta'    => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'per_page'     => $conversations->perPage(),
                'total'        => $conversations->total(),
            ],
        ]);
    }

    /**
     * Archive a conversation.
     */
    public function archiveConversation(Request $request, int $conversationId): JsonResponse
    {
        try {
            $this->chatService->archiveConversation($conversationId, auth()->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Conversation archived',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Reactivate a conversation.
     */
    public function reactivateConversation(Request $request, int $conversationId): JsonResponse
    {
        try {
            $this->chatService->reactivateConversation($conversationId, auth()->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Conversation reactivated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
