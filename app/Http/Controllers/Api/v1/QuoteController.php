<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Rfq;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    use ApiResponse;

    /**
     * List quotes with pagination
     *
     * This method retrieves a list of quotes available for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Quote::with([
            'rfq:id,initial_quantity,shipping_country,buyer_message,status',
            'items:id,quote_id,product_id,quantity,unit_price,notes',
            'items.product:id,name,brand',
        ])
            ->whereHas('rfq', function ($q) use ($user) {
                $q->where('seller_id', $user->id)->orWhere('buyer_id', $user->id);
            });

        $quotes = $query->paginate(15);

        return $this->apiResponse(
            QuoteResource::collection($quotes),
            'Quotes retrieved successfully',
            200,
            $this->getPaginationMeta($quotes)
        );
    }

    /**
     * Store a newly created quote from RFQ.
     *
     * This method creates a new quote based on RFQ requirements.
     */
    public function store(CreateQuoteRequest $request): JsonResponse
    {
        $user = Auth::user();

        $rfq = Rfq::with('initialProduct')->find($request->rfq_id);

        if (! $rfq) {
            return $this->apiResponseErrors(
                'RFQ not found',
                ['rfq_id' => 'The selected RFQ does not exist'],
                404
            );
        }

        if ($rfq->seller_id !== $user->id) {
            return $this->apiResponseErrors(
                'Access denied',
                ['error' => 'You can only create quotes for RFQs assigned to you'],
                403
            );
        }

        DB::beginTransaction();

        try {
            $totalPrice = 0;

            foreach ($request->items as $item) {
                $subtotal = $item['unit_price'] * $item['quantity'];
                $totalPrice += $subtotal;
            }

            $quote = Quote::create([
                'rfq_id'         => $rfq->id,
                'total_price'    => $totalPrice,
                'seller_message' => $request->seller_message ?? "Quote for RFQ #{$rfq->id}",
                'status'         => Quote::STATUS_SENT,
            ]);

            foreach ($request->items as $item) {
                $quote->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'notes'      => $item['notes'] ?? '',
                ]);
            }

            $rfq->transitionTo(Rfq::STATUS_QUOTED);

            $quote->load(['rfq.buyer', 'rfq.seller', 'items.product']);

            DB::commit();

            return $this->apiResponse(
                new QuoteResource($quote),
                'Quote created and sent successfully, RFQ marked as quoted',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();

            return $this->apiResponseErrors(
                'Failed to create quote',
                ['error' => 'An error occurred while creating the quote', 'details' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified quote.
     *
     * This method retrieves a specific quote by its ID and checks access permissions.
     */
    public function show(string $id): JsonResponse
    {
        $quote = Quote::with(['rfq.buyer', 'rfq.seller', 'rfq.initialProduct', 'items.product'])
            ->find($id);

        if (! $quote) {
            return $this->apiResponseErrors(
                'Quote not found',
                ['error' => 'The requested quote does not exist'],
                404
            );
        }

        $user = Auth::user();

        $hasAccess = false;
        if ($quote->rfq && ($quote->rfq->seller_id === $user->id || $quote->rfq->buyer_id === $user->id)) {
            $hasAccess = true;
        }

        if (! $hasAccess) {
            return $this->apiResponseErrors(
                'Access denied',
                ['error' => 'You do not have permission to view this quote'],
                403
            );
        }

        return $this->apiResponse(
            new QuoteResource($quote),
            'Quote retrieved successfully'
        );
    }

    /**
     * Update an existing quote.
     *
     * This method updates a quote with new items and pricing information.
     * It handles role-based access control for sellers and buyers.
     * Sellers can update their own quotes, while buyers can only accept/reject quotes.
     */
    public function update(UpdateQuoteRequest $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $quote = Quote::with(['rfq.initialProduct', 'items'])->find($id);

        if (! $quote) {
            return $this->apiResponseErrors(
                'Quote not found',
                ['error' => 'The requested quote does not exist'],
                404
            );
        }

        $isSeller = $user->hasRole('seller');
        $isBuyer = $user->hasRole('buyer');
        $isAdmin = $user->hasRole('admin');

        if ($isSeller && (! $quote->rfq || $quote->rfq->seller_id !== $user->id)) {
            return $this->apiResponseErrors(
                'Access denied',
                ['error' => 'You can only update quotes for RFQs assigned to you'],
                403
            );
        }

        if ($isBuyer && (! $quote->rfq || $quote->rfq->buyer_id !== $user->id)) {
            return $this->apiResponseErrors(
                'Access denied',
                ['error' => 'You can only accept/reject quotes for your own RFQs'],
                403
            );
        }

        if (! $isAdmin && ! $isSeller && ! $isBuyer) {
            return $this->apiResponseErrors(
                'Access denied',
                ['error' => 'You do not have permission to update quotes'],
                403
            );
        }

        DB::beginTransaction();

        try {
            $newStatus = $quote->status;
            $totalPrice = $quote->total_price;

            if ($request->has('status')) {
                $requestedStatus = $request->status;

                if (! $quote->canTransitionTo($requestedStatus)) {
                    return $this->apiResponseErrors(
                        'Invalid status transition',
                        ['error' => "Quote cannot transition from '{$quote->status}' to '{$requestedStatus}'"],
                        422
                    );
                }

                if ($requestedStatus === Quote::STATUS_ACCEPTED || $requestedStatus === Quote::STATUS_REJECTED) {
                    if (! $isBuyer && ! $isAdmin) {
                        return $this->apiResponseErrors(
                            'Access denied',
                            ['error' => 'Only buyers can accept or reject quotes'],
                            403
                        );
                    }
                }

                if ($requestedStatus === Quote::STATUS_SENT) {
                    if (! $isSeller && ! $isAdmin) {
                        return $this->apiResponseErrors(
                            'Access denied',
                            ['error' => 'Only sellers can send quotes'],
                            403
                        );
                    }
                }

                $newStatus = $requestedStatus;
            }

            if ($request->has('items') && ($isSeller || $isAdmin)) {
                $totalPrice = 0;

                foreach ($request->items as $itemData) {
                    if (isset($itemData['id'])) {
                        $quoteItem = QuoteItem::find($itemData['id']);

                        if (! $quoteItem || $quoteItem->quote_id !== $quote->id) {
                            throw new Exception('Invalid quote item ID');
                        }

                        $quoteItem->update([
                            'quantity'   => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'notes'      => $itemData['notes'] ?? $quoteItem->notes,
                        ]);
                    } else {
                        $quoteItem = QuoteItem::create([
                            'quote_id'   => $quote->id,
                            'product_id' => $itemData['product_id'],
                            'quantity'   => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'notes'      => $itemData['notes'] ?? null,
                        ]);
                    }

                    $totalPrice += $itemData['quantity'] * $itemData['unit_price'];
                }
            }

            $updateData = [];

            if ($isSeller || $isAdmin) {
                $updateData['total_price'] = $totalPrice;
                if ($request->has('seller_message')) {
                    $updateData['seller_message'] = $request->seller_message;
                }
            }

            if ($newStatus !== $quote->status) {
                $updateData['status'] = $newStatus;
            }

            if (! empty($updateData)) {
                $quote->update($updateData);
            }

            if ($newStatus === Quote::STATUS_ACCEPTED && $quote->status !== Quote::STATUS_ACCEPTED) {
                $quote->transitionTo(Quote::STATUS_ACCEPTED);
            } elseif ($newStatus === Quote::STATUS_REJECTED && $quote->status !== Quote::STATUS_REJECTED) {
                $quote->transitionTo(Quote::STATUS_REJECTED);
            } elseif ($newStatus === Quote::STATUS_SENT && $quote->status !== Quote::STATUS_SENT) {
                $quote->transitionTo(Quote::STATUS_SENT);
            }

            $quote->load(['rfq.buyer', 'rfq.seller', 'items.product']);

            DB::commit();

            $message = match ($newStatus) {
                Quote::STATUS_ACCEPTED => 'Quote accepted successfully. RFQ has also been accepted.',
                Quote::STATUS_REJECTED => 'Quote rejected successfully.',
                Quote::STATUS_SENT     => 'Quote updated and sent successfully. RFQ marked as quoted.',
                default                => 'Quote updated successfully'
            };

            return $this->apiResponse(
                new QuoteResource($quote),
                $message
            );
        } catch (Exception $e) {
            DB::rollBack();

            return $this->apiResponseErrors(
                'Failed to update quote',
                ['error' => 'An error occurred while updating the quote', 'details' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Remove the specified quote from storage.
     *
     * Only sellers can delete their own quotes and only if status is 'pending'
     */
    public function destroy(string $id): JsonResponse
    {
        $quote = Quote::find($id);

        if (! $quote) {
            return $this->apiResponseErrors(
                'Quote not found',
                ['error' => 'The requested quote does not exist'],
                404
            );
        }

        $user = Auth::user();

        if (! $quote->rfq || $quote->rfq->seller_id !== $user->id) {
            return $this->apiResponseErrors(
                'Access denied',
                ['error' => 'You can only delete your own quotes'],
                403
            );
        }

        if ($quote->status !== Quote::STATUS_PENDING) {
            return $this->apiResponseErrors(
                'Cannot delete quote',
                ['error' => 'Can only delete quotes with status: pending'],
                422
            );
        }

        try {
            $quote->delete();

            return $this->apiResponse(
                null,
                'Quote deleted successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to delete quote',
                ['error' => 'An error occurred while deleting the quote'],
                500
            );
        }
    }
}
