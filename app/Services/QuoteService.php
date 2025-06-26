<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Rfq;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuoteService
{
    /**
     * Get paginated quotes with filtering and sorting
     *
     * @param Request $request - The request containing filter and sort parameters
     * @param int|null $userId - User ID to filter quotes (null for admin to see all)
     * @param int $perPage - Number of items per page
     */
    public function getWithFilters(Request $request, ?int $userId = null, int $perPage = 15): LengthAwarePaginator
    {
        $queryHandler = new QueryHandler($request);

        $query = Quote::with([
            'rfq',
            'conversation',
            'items',
            'items.product',
        ]);

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('seller_id', $userId)->orWhere('buyer_id', $userId);
            });
        }

        $query = $queryHandler
            ->setBaseQuery($query)
            ->setAllowedSorts(['id', 'total_price', 'status', 'created_at', 'updated_at', 'seller_message', 'rfq.initial_quantity', 'rfq.shipping_country', 'rfq.status',
            ])
            ->setAllowedFilters(['id', 'total_price', 'status', 'seller_message', 'rfq_id', 'conversation_id', 'seller_id', 'buyer_id', 'created_at', 'updated_at', 'rfq.initial_quantity', 'rfq.shipping_country', 'rfq.status', 'rfq.buyer_id', 'rfq.seller_id', 'items.product.name', 'items.product.brand',
            ])
            ->apply();

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new quote
     */
    public function create(array $data, int $userId): Quote
    {
        $rfq = null;
        $conversation = null;

        if (! empty($data['rfq_id'])) {
            $rfq = $this->validateAndGetRfq($data['rfq_id'], $userId);
        } elseif (! empty($data['conversation_id'])) {
            $conversation = $this->validateAndGetConversation($data['conversation_id'], $userId);
        } else {
            throw new InvalidArgumentException('Either rfq_id or conversation_id must be provided');
        }

        return DB::transaction(function () use ($data, $userId, $rfq, $conversation) {
            $totalPrice = $this->calculateTotalPrice($data['items']);

            $quote = Quote::create([
                'rfq_id'          => $data['rfq_id'] ?? null,
                'conversation_id' => $data['conversation_id'] ?? null,
                'seller_id'       => $userId,
                'buyer_id'        => $rfq ? $rfq->buyer_id : ($conversation ? $this->getOtherParticipant($conversation, $userId) : null),
                'total_price'     => $totalPrice,
                'seller_message'  => $data['seller_message'] ?? $this->getDefaultMessage($rfq),
                'status'          => Quote::STATUS_SENT,
            ]);

            $this->createQuoteItems($quote->id, $data['items']);

            if ($rfq) {
                $rfq->transitionTo(Rfq::STATUS_QUOTED);
                $quote->load(['rfq.buyer', 'rfq.seller', 'items.product']);
            } else {
                $quote->load(['items.product', 'conversation']);
            }

            return $quote;
        });
    }

    /**
     * Get quote by ID with access validation
     */
    public function findWithAccess(int $quoteId, int $userId): Quote
    {
        $quote = Quote::with(['rfq', 'rfq.buyer', 'rfq.seller', 'rfq.initialProduct', 'conversation', 'items', 'items.product'])
            ->findOrFail($quoteId);

        if (! $this->canAccessQuote($quote, $userId)) {
            throw new AuthorizationException('You do not have permission to view this quote');
        }

        return $quote;
    }

    /**
     * Update quote
     */
    public function update(int $quoteId, array $data, int $userId, array $userRoles): Quote
    {
        $quote = Quote::with(['rfq', 'rfq.initialProduct', 'items'])->findOrFail($quoteId);

        $this->validateUpdateAccess($quote, $userId, $userRoles);

        return DB::transaction(function () use ($quote, $data, $userRoles) {
            $updateData = [];
            $totalPrice = $quote->total_price;

            $isBuyer = in_array('buyer', $userRoles);
            $isSeller = in_array('seller', $userRoles);
            $isAdmin = in_array('admin', $userRoles);

            if ($isBuyer && ! $isAdmin) {
                if (! empty($data['items'])) {
                    throw new AuthorizationException('Buyers cannot modify quote items');
                }
                if (isset($data['seller_message'])) {
                    throw new AuthorizationException('Buyers cannot modify seller messages');
                }
                if (! empty($data['status'])) {
                    $this->validateStatusTransition($quote, $data['status'], $userRoles);
                    $updateData['status'] = $data['status'];
                }
            } else {

                if (! empty($data['status'])) {
                    $this->validateStatusTransition($quote, $data['status'], $userRoles);
                    $updateData['status'] = $data['status'];
                }

                if (! empty($data['items']) && $this->canUpdateItems($userRoles)) {
                    $totalPrice = $this->updateQuoteItems($quote, $data['items']);
                    $updateData['total_price'] = $totalPrice;
                }

                if (isset($data['seller_message']) && $this->canUpdateItems($userRoles)) {
                    $updateData['seller_message'] = $data['seller_message'];
                }
            }

            if (! empty($updateData)) {
                $quote->update($updateData);
            }

            if (isset($updateData['status']) && $updateData['status'] === Quote::STATUS_ACCEPTED) {
                if (! $quote->hasContract()) {
                    $quote->acceptAndCreateContract();
                }
            }

            $quote->load(['rfq.buyer', 'rfq.seller', 'items.product', 'contract']);

            return $quote;
        });
    }

    /**
     * Delete quote
     */
    public function delete(int $quoteId, int $userId): void
    {
        $quote = Quote::with(['rfq', 'conversation'])->findOrFail($quoteId);

        $canDelete = false;

        if ($quote->rfq && $quote->rfq->seller_id === $userId) {
            $canDelete = true;
        }

        if ($quote->conversation_id && $quote->conversation) {
            if (in_array($userId, $quote->conversation->participant_ids)) {
                $canDelete = true;
            }
        }

        if (! $canDelete) {
            throw new AuthorizationException('You can only delete your own quotes');
        }

        if ($quote->status !== Quote::STATUS_SENT) {
            throw new InvalidArgumentException('Can only delete quotes with status: sent');
        }

        $quote->delete();
    }

    /**
     * Get success message for quote status
     */
    public function getStatusMessage(string $status): string
    {
        return match ($status) {
            Quote::STATUS_ACCEPTED => 'Quote accepted successfully and contract created.',
            Quote::STATUS_REJECTED => 'Quote rejected successfully.',
            Quote::STATUS_SENT     => 'Quote updated and sent successfully. RFQ marked as quoted.',
            default                => 'Quote updated successfully'
        };
    }

    // Private helper methods
    private function validateAndGetRfq(int $rfqId, int $userId): Rfq
    {
        $rfq = Rfq::with('initialProduct')->find($rfqId);

        if (! $rfq) {
            throw new ModelNotFoundException('RFQ not found');
        }

        if ($rfq->seller_id !== $userId) {
            throw new AuthorizationException('You can only create quotes for RFQs assigned to you');
        }

        return $rfq;
    }

    private function validateAndGetConversation(int $conversationId, int $userId): Conversation
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (! in_array($userId, $conversation->participant_ids)) {
            throw new AuthorizationException('You are not a participant in this conversation');
        }

        return $conversation;
    }

    private function calculateTotalPrice(array $items): float
    {
        return collect($items)->sum(fn ($item) => $item['unit_price'] * $item['quantity']);
    }

    private function createQuoteItems(int $quoteId, array $items): void
    {
        foreach ($items as $item) {
            QuoteItem::create([
                'quote_id'   => $quoteId,
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'notes'      => $item['notes'] ?? '',
            ]);
        }
    }

    private function getDefaultMessage(?Rfq $rfq): string
    {
        return $rfq ? "Quote for RFQ #{$rfq->id}" : 'Quote from conversation';
    }

    private function getOtherParticipant(Conversation $conversation, int $userId): int
    {
        return collect($conversation->participant_ids)
            ->reject(fn ($id) => $id === $userId)
            ->first();
    }

    private function canAccessQuote(Quote $quote, int $userId): bool
    {
        if ($quote->rfq && ($quote->rfq->seller_id === $userId || $quote->rfq->buyer_id === $userId)) {
            return true;
        }

        if ($quote->conversation_id && $quote->conversation && in_array($userId, $quote->conversation->participant_ids)) {
            return true;
        }

        if ($quote->seller_id === $userId || $quote->buyer_id === $userId) {
            return true;
        }

        return false;
    }

    private function validateUpdateAccess(Quote $quote, int $userId, array $userRoles): void
    {
        $isSeller = in_array('seller', $userRoles);
        $isBuyer = in_array('buyer', $userRoles);
        $isAdmin = in_array('admin', $userRoles);

        if ($isSeller) {
            $canUpdate = false;

            if ($quote->rfq && $quote->rfq->seller_id === $userId) {
                $canUpdate = true;
            }

            if ($quote->conversation_id && $quote->conversation) {
                if (in_array($userId, $quote->conversation->participant_ids)) {
                    $canUpdate = true;
                }
            }

            if (! $canUpdate) {
                throw new AuthorizationException('You can only update your own quotes');
            }
        }

        if ($isBuyer) {
            $canUpdate = false;

            if ($quote->rfq && $quote->rfq->buyer_id === $userId) {
                $canUpdate = true;
            }

            if ($quote->conversation_id && $quote->conversation) {
                if (in_array($userId, $quote->conversation->participant_ids)) {
                    $canUpdate = true;
                }
            }

            if (! $canUpdate) {
                throw new AuthorizationException('You can only accept/reject your own quotes');
            }
        }

        if (! $isAdmin && ! $isSeller && ! $isBuyer) {
            throw new AuthorizationException('You do not have permission to update quotes');
        }
    }

    private function validateStatusTransition(Quote $quote, string $newStatus, array $userRoles): void
    {
        if (! $quote->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException("Quote cannot transition from '{$quote->status}' to '{$newStatus}'");
        }

        $isBuyer = in_array('buyer', $userRoles);
        $isSeller = in_array('seller', $userRoles);
        $isAdmin = in_array('admin', $userRoles);

        if (in_array($newStatus, [Quote::STATUS_ACCEPTED, Quote::STATUS_REJECTED]) && ! $isBuyer && ! $isAdmin) {
            throw new AuthorizationException('Only buyers can accept or reject quotes');
        }

        if ($newStatus === Quote::STATUS_SENT && ! $isSeller && ! $isAdmin) {
            throw new AuthorizationException('Only sellers can send quotes');
        }
    }

    private function canUpdateItems(array $userRoles): bool
    {
        return in_array('seller', $userRoles) || in_array('admin', $userRoles);
    }

    private function updateQuoteItems(Quote $quote, array $items): float
    {
        $totalPrice = 0;

        foreach ($items as $itemData) {
            if (isset($itemData['id'])) {
                $quoteItem = QuoteItem::where('id', $itemData['id'])
                    ->where('quote_id', $quote->id)
                    ->firstOrFail();

                $quoteItem->update([
                    'quantity'   => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'notes'      => $itemData['notes'] ?? $quoteItem->notes,
                ]);
            } else {
                QuoteItem::create([
                    'quote_id'   => $quote->id,
                    'product_id' => $itemData['product_id'],
                    'quantity'   => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'notes'      => $itemData['notes'] ?? null,
                ]);
            }

            $totalPrice += $itemData['quantity'] * $itemData['unit_price'];
        }

        return $totalPrice;
    }
}
