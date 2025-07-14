<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Rfq;
use App\Models\User;
use App\Notifications\QuoteStatusChangedNotification;
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
     */
    public function getWithFilters(Request $request, ?int $userId = null, ?string $userType = null, int $perPage = 15): LengthAwarePaginator
    {
        $queryHandler = new QueryHandler($request);

        $query = Quote::with([
            'directBuyer.company',
            'directSeller.company',
            'rfq.buyer.company',
            'rfq.seller.company',
            'rfq',
            'conversation',
            'items',
            'items.product',
            'contract',
        ]);

        if ($userId && $userType) {
            if ($userType === 'buyer') {
                $query->forBuyer($userId);
            } elseif ($userType === 'seller') {
                $query->forSeller($userId);
            }
        } elseif ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('seller_id', $userId)->orWhere('buyer_id', $userId);
            });
        }

        $query = $queryHandler
            ->setBaseQuery($query)
            ->setAllowedSorts([
                'id',
                'total_price',
                'status',
                'created_at',
                'updated_at',
                'seller_message',
                'rfq.initial_quantity',
                'rfq.shipping_country',
                'rfq.status',
            ])
            ->setAllowedFilters([
                'id',
                'total_price',
                'status',
                'seller_message',
                'rfq_id',
                'conversation_id',
                'seller_id',
                'buyer_id',
                'created_at',
                'updated_at',
                'rfq.initial_quantity',
                'rfq.shipping_country',
                'rfq.status',
                'rfq.buyer_id',
                'rfq.seller_id',
                'items.product.name',
                'items.product.brand',
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
                $quote->load(['directBuyer.company', 'directSeller.company', 'rfq.buyer', 'rfq.seller', 'items.product', 'contract']);
            } else {
                $quote->load(['directBuyer.company', 'directSeller.company', 'items.product', 'conversation', 'contract']);
            }

            return $quote;
        });
    }

    /**
     * Get quote by ID with access validation
     */
    public function findWithAccess(int $quoteId, int $userId): Quote
    {
        $quote = Quote::with([
            'directBuyer.company',
            'directSeller.company',
            'rfq',
            'rfq.buyer',
            'rfq.seller',
            'rfq.initialProduct',
            'conversation',
            'items',
            'items.product',
            'contract',
        ])->findOrFail($quoteId);

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
        $quote = Quote::with(['directBuyer.company',
            'directSeller.company',
            'rfq',
            'rfq.buyer',
            'rfq.seller', 'rfq', 'rfq.initialProduct', 'items', 'contract'])->findOrFail($quoteId);

        $this->validateUpdateAccess($quote, $userId, $userRoles);

        return DB::transaction(function () use ($quote, $data, $userRoles, $userId) {
            $updateData = [];
            $totalPrice = $quote->total_price;

            $user = User::findOrFail($userId);
            $isBuyer = $user->canActInRole('buyer', $quote);
            $isSeller = $user->canActInRole('seller', $quote);
            $isAdmin = in_array('admin', $userRoles);

            if ($isBuyer && ! $isAdmin) {
                if (! empty($data['items'])) {
                    throw new AuthorizationException('Buyers cannot modify quote items');
                }
                if (isset($data['seller_message'])) {
                    throw new AuthorizationException('Buyers cannot modify seller messages');
                }
                if (! empty($data['status'])) {
                    $this->validateStatusTransition($quote, $data['status'], $userRoles, $userId);
                    $updateData['status'] = $data['status'];
                    if ($data['status'] === Quote::STATUS_ACCEPTED) {
                        $updateData['accepted_at'] = now();
                    }

                }
            } else {

                if (! empty($data['status'])) {
                    $this->validateStatusTransition($quote, $data['status'], $userRoles, $userId);
                    $updateData['status'] = $data['status'];
                    if ($data['status'] === Quote::STATUS_ACCEPTED) {
                        $updateData['accepted_at'] = now();
                    }
                }

                if (! empty($data['items']) && $this->canUpdateItems($userRoles, $quote, $userId)) {
                    $totalPrice = $this->updateQuoteItems($quote, $data['items']);
                    $updateData['total_price'] = $totalPrice;
                }

                if (isset($data['seller_message']) && $this->canUpdateItems($userRoles, $quote, $userId)) {
                    $updateData['seller_message'] = $data['seller_message'];
                }
            }

            if (! empty($updateData)) {
                $originalStatus = $quote->status; // Capture original status before update
                $quote->update($updateData);

                // Dispatch notification if status changed
                if (isset($updateData['status']) && $updateData['status'] !== $originalStatus) {
                    if ($isBuyer) {
                        // Buyer made the change, notify the seller
                        if ($quote->seller) {
                            $quote->seller->notify(new QuoteStatusChangedNotification($quote));
                        }
                    } elseif ($isSeller) {
                        // Seller made the change, notify the buyer
                        if ($quote->buyer) {
                            $quote->buyer->notify(new QuoteStatusChangedNotification($quote));
                        }
                    }
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
        $quote = Quote::with(['directBuyer.company',
            'directSeller.company',
            'rfq',
            'rfq.buyer',
            'rfq.seller', 'rfq', 'conversation'])->findOrFail($quoteId);

        $canDelete = false;

        if ($quote->rfq && $quote->rfq->seller_id === $userId) {
            $canDelete = true;
        }

        if ($quote->conversation_id && $quote->conversation) {
            if ($quote->conversation->isParticipant($userId)) {
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
            Quote::STATUS_ACCEPTED => 'Quote accepted successfully. The seller can now create a contract from this accepted quote.',
            Quote::STATUS_REJECTED => 'Quote rejected successfully.',
            Quote::STATUS_SENT     => 'Quote updated and sent successfully. RFQ marked as quoted.',
            default                => 'Quote updated successfully'
        };
    }

    private function validateAndGetRfq(int $rfqId, int $userId): Rfq
    {
        $rfq = Rfq::with('initialProduct')->find($rfqId);

        if (! $rfq) {
            throw new ModelNotFoundException('RFQ not found');
        }

        $user = User::findOrFail($userId);
        if (! $user->canActInRole('seller', $rfq) || $rfq->seller_id !== $userId) {
            throw new AuthorizationException('You can only create quotes for RFQs where you are the seller');
        }

        return $rfq;
    }

    private function validateAndGetConversation(int $conversationId, int $userId): Conversation
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (! $conversation->isParticipant($userId)) {
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
        $otherParticipant = $conversation->getOtherParticipant($userId);

        if (! $otherParticipant) {
            throw new InvalidArgumentException('Invalid participant in conversation');
        }

        return $otherParticipant->id;
    }

    private function canAccessQuote(Quote $quote, int $userId): bool
    {
        if ($quote->rfq && ($quote->rfq->seller_id === $userId || $quote->rfq->buyer_id === $userId)) {
            return true;
        }

        if ($quote->conversation_id && $quote->conversation && $quote->conversation->isParticipant($userId)) {
            return true;
        }

        if ($quote->seller_id === $userId || $quote->buyer_id === $userId) {
            return true;
        }

        return false;
    }

    private function validateUpdateAccess(Quote $quote, int $userId, array $userRoles): void
    {
        $user = User::findOrFail($userId);
        $isAdmin = in_array('admin', $userRoles);

        if ($isAdmin) {
            return;
        }

        $canUpdate = false;
        $contextRoles = [];

        if ($user->canActInRole('seller', $quote)) {
            if ($quote->rfq && $quote->rfq->seller_id === $userId) {
                $canUpdate = true;
                $contextRoles[] = 'seller';
            }

            if ($quote->conversation_id && $quote->conversation) {
                if ($quote->conversation->isParticipant($userId)) {
                    $canUpdate = true;
                }
            }
            if ($quote->conversation_id && $quote->conversation && in_array($userId, $quote->conversation->participant_ids)) {
                $canUpdate = true;
                $contextRoles[] = 'seller';
            }
        }

        if ($user->canActInRole('buyer', $quote)) {
            if ($quote->rfq && $quote->rfq->buyer_id === $userId) {
                $canUpdate = true;
                $contextRoles[] = 'buyer';
            }

            if ($quote->conversation_id && $quote->conversation) {
                if ($quote->conversation->isParticipant($userId)) {
                    $canUpdate = true;
                }
            }
            if ($quote->conversation_id && $quote->conversation && in_array($userId, $quote->conversation->participant_ids)) {
                $canUpdate = true;
                $contextRoles[] = 'buyer';
            }
        }

        if (! $canUpdate) {
            throw new AuthorizationException('You do not have permission to update this quote');
        }
    }

    private function validateStatusTransition(Quote $quote, string $newStatus, array $userRoles, int $userId): void
    {
        if (! $quote->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException("Quote cannot transition from '{$quote->status}' to '{$newStatus}'");
        }

        $user = User::findOrFail($userId);
        $isAdmin = in_array('admin', $userRoles);

        if ($isAdmin) {
            return;
        }

        if (in_array($newStatus, [Quote::STATUS_ACCEPTED, Quote::STATUS_REJECTED])) {
            if (! $user->canActInRole('buyer', $quote)) {
                throw new AuthorizationException('Only buyers can accept or reject quotes');
            }

            $isBuyerInContext = ($quote->rfq && $quote->rfq->buyer_id === $userId) ||
                ($quote->buyer_id === $userId) ||
                ($quote->conversation_id && $quote->conversation && in_array($userId, $quote->conversation->participant_ids));

            if (! $isBuyerInContext) {
                throw new AuthorizationException('You can only accept/reject quotes where you are the buyer');
            }
        }

        if ($newStatus === Quote::STATUS_SENT) {
            if (! $user->canActInRole('seller', $quote)) {
                throw new AuthorizationException('Only sellers can send quotes');
            }

            $isSellerInContext = ($quote->rfq && $quote->rfq->seller_id === $userId) ||
                ($quote->seller_id === $userId) ||
                ($quote->conversation_id && $quote->conversation && in_array($userId, $quote->conversation->participant_ids));

            if (! $isSellerInContext) {
                throw new AuthorizationException('You can only send quotes where you are the seller');
            }
        }
    }

    private function canUpdateItems(array $userRoles, ?Quote $quote = null, ?int $userId = null): bool
    {
        if (in_array('admin', $userRoles)) {
            return true;
        }

        if (! $quote || ! $userId) {
            return in_array('seller', $userRoles);
        }

        $user = User::findOrFail($userId);

        return $user && $user->canActInRole('seller', $quote);
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
