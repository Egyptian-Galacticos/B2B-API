<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Requests\Chat\StartConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\User;
use App\Services\ChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use ApiResponse;

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
            $request->get('size', 10)
        );

        return $this->apiResponse(
            data: ConversationResource::collection($conversations->items()),
            message: 'conversations return',
            meta: $this->getPaginationMeta($conversations)

        );
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
        $otherUser = User::findOrFail($otherUserId);

        if ($user->isSeller() && $otherUser->isBuyer()) {
            $sellerId = $userId;
            $buyerId = $otherUserId;
        } elseif ($user->isBuyer() && $otherUser->isSeller()) {
            $sellerId = $otherUserId;
            $buyerId = $userId;
        } else {
            return $this->apiResponseErrors(
                'Conversations can only be started between buyers and sellers.',
                400
            );
        }

        $conversation = $this->chatService->getOrCreateConversation(
            $sellerId,
            $buyerId,
            $request->validated('type', 'direct'),
            $request->validated('title')
        );

        return $this->apiResponse(
            data: new ConversationResource($conversation->load(['seller', 'buyer', 'lastMessage.sender'])),
            message: 'Conversation retrieved successfully'
        );
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
                $request->get('size', 50)
            );

            return $this->apiResponse(
                data: MessageResource::collection($messages->items()),
                message: '',
                meta: $this->getPaginationMeta($messages)
            );
        } catch (\Exception $e) {

            return $this->apiResponseErrors(
                message: 'error on reserve data',
                errors: [
                    'error' => $e->getMessage(),
                ],
                status: 403
            );
        }
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        try {
            $attachments = [];

            $allFiles = $request->allFiles();

            if (isset($allFiles['attachments'])) {
                foreach ($allFiles['attachments'] as $index => $attachmentData) {
                    if (isset($attachmentData['file']) && $attachmentData['file']->isValid()) {
                        $attachments[] = [
                            'file'      => $attachmentData['file'],
                            'file_name' => $request->input("attachments.{$index}.file_name") ?? $attachmentData['file']->getClientOriginalName(),
                        ];
                    }
                }
            }

            $message = $this->chatService->sendMessage(
                $conversationId,
                auth()->user()->id,
                $request->validated('content') ?? '',
                $request->validated('type', 'text'),
                $attachments
            );

            return $this->apiResponse(
                data: new MessageResource($message),
                message: 'Message sent successfully',
                status: 201
            );
        } catch (\Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to send message',
                errors: [
                    'error' => $e->getMessage(),
                ],
                status: 403
            );
        }
    }

    /**
     * Mark all messages in a conversation as read.
     */
    public function markConversationAsRead(Request $request, int $conversationId): JsonResponse
    {
        try {
            $this->chatService->markConversationAsRead($conversationId, auth()->user()->id);

            return $this->apiResponse(
                message: 'Messages marked as read',
                status: 200
            );
        } catch (\Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to mark messages as read',
                errors: [
                    'error' => $e->getMessage(),
                ],
                status: 403
            );
        }
    }

    /**
     * Get unread message count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->chatService->getUnreadMessageCount(auth()->user()->id);

        return $this->apiResponse(
            data: ['count' => $count],
            message: 'Unread message count retrieved successfully'
        );
    }

    /**
     * Search conversations.
     */
    public function searchConversations(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'size'  => 'integer|min:1|max:100',
        ]);

        $conversations = $this->chatService->searchConversations(
            auth()->user()->id,
            $request->get('query'),
            $request->get('size', 15)
        );

        return $this->apiResponse(
            data: ConversationResource::collection($conversations->items()),
            message: 'Conversations found',
            meta: $this->getPaginationMeta($conversations)
        );
    }

    /**
     * Mark a message as read.
     */
    public function markMessageAsRead(Request $request, int $messageId): JsonResponse
    {
        try {
            $this->chatService->markAsRead($messageId, auth()->user()->id);

            return $this->apiResponse(
                message: 'Message marked as read',
                status: 200
            );
        } catch (\Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to mark message as read',
                errors: [
                    'error' => $e->getMessage(),
                ],
                status: 403
            );
        }
    }
}
