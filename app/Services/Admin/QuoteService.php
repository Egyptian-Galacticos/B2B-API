<?php

namespace App\Services\Admin;

use App\Http\Resources\Admin\AdminQuoteResource;
use App\Models\Quote;
use App\Services\QueryHandler;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use InvalidArgumentException;

class QuoteService
{
    /**
     * Get all quotes with filtering and pagination for admin oversight.
     */
    public function getAllQuotesWithFilters(array $filters, Request $request): LengthAwarePaginator
    {
        $query = Quote::with([
            'directBuyer.company',
            'directSeller.company',
            'rfq.buyer.company',
            'rfq.seller.company',
            'rfq',
            'conversation',
            'items.product',
            'contract',
        ]);

        $queryHandler = new QueryHandler($request);
        $queryHandler->setBaseQuery($query)
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
            ]);

        $this->applyCustomFilters($query, $filters);

        $filteredQuery = $queryHandler->apply();

        return $filteredQuery->paginate($request->size ?? 10);
    }

    /**
     * Get quote by ID for admin oversight.
     */
    public function getQuoteById(int $id): Quote
    {
        return Quote::with([
            'directBuyer.company',
            'directSeller.company',
            'rfq.buyer.company',
            'rfq.seller.company',
            'rfq',
            'conversation',
            'items.product',
            'contract',
        ])->findOrFail($id);
    }

    /**
     * Update quote status for admin oversight.
     */
    public function updateQuoteStatus(int $id, string $newStatus): Quote
    {
        $quote = Quote::findOrFail($id);

        if (! $quote->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException("Cannot transition from {$quote->status} to {$newStatus}");
        }

        $quote->update(['status' => $newStatus]);
        $quote->load([
            'directBuyer.company',
            'directSeller.company',
            'rfq.buyer.company',
            'rfq.seller.company',
            'rfq',
            'conversation',
            'items.product',
            'contract',
        ]);

        return $quote;
    }

    /**
     * Get success message for quote status
     */
    public function getStatusMessage(string $status): string
    {
        return match ($status) {
            Quote::STATUS_ACCEPTED => 'Quote accepted successfully. The seller can now create a contract from this accepted quote.',
            Quote::STATUS_REJECTED => 'Quote rejected successfully.',
            Quote::STATUS_SENT     => 'Quote updated and sent successfully.',
            default                => 'Quote updated successfully'
        };
    }

    /**
     * Handle bulk actions on quotes.
     */
    public function bulkActionQuotes(array $quoteIds, string $action, ?string $status = null): array
    {
        try {
            $validActions = ['delete', 'update_status'];

            if (! in_array($action, $validActions)) {
                return [
                    'success' => false,
                    'message' => 'Invalid bulk action',
                    'status'  => 400,
                ];
            }

            if ($action === 'update_status' && ! $status) {
                return [
                    'success' => false,
                    'message' => 'Status is required for update_status action',
                    'status'  => 400,
                ];
            }

            $quotes = Quote::whereIn('id', $quoteIds)->get();

            if ($quotes->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No quotes found for the provided IDs',
                    'status'  => 404,
                ];
            }

            $successCount = 0;
            $errors = [];
            $updatedQuotes = [];

            foreach ($quotes as $quote) {
                try {
                    switch ($action) {
                        case 'delete':
                            $quote->delete();
                            $successCount++;
                            break;
                        case 'update_status':
                            if (! $quote->canTransitionTo($status)) {
                                $errors[] = "Quote {$quote->id}: Cannot transition from {$quote->status} to {$status}";
                            } else {
                                $quote->update(['status' => $status]);
                                $quote->load([
                                    'directBuyer.company',
                                    'directSeller.company',
                                    'rfq.buyer.company',
                                    'rfq.seller.company',
                                    'rfq',
                                    'conversation',
                                    'items.product',
                                    'contract',
                                ]);
                                $updatedQuotes[] = $quote;
                                $successCount++;
                            }
                            break;
                    }
                } catch (Exception $e) {
                    $errors[] = "Quote {$quote->id}: ".$e->getMessage();
                }
            }

            $message = $successCount > 0
                ? "Bulk action completed successfully on {$successCount} quotes"
                : 'Bulk action failed';

            $responseData = [];

            if ($action === 'update_status' && ! empty($updatedQuotes)) {
                $responseData['quotes'] = AdminQuoteResource::collection($updatedQuotes);
            }

            return [
                'success' => $successCount > 0,
                'message' => $message,
                'data'    => $responseData,
                'status'  => $successCount > 0 ? 200 : 400,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to perform bulk action',
                'status'  => 500,
            ];
        }
    }

    /**
     * Apply custom filters for quote queries.
     */
    private function applyCustomFilters($query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['buyer_id'])) {
            $query->where('buyer_id', $filters['buyer_id']);
        }

        if (! empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (! empty($filters['rfq_id'])) {
            $query->where('rfq_id', $filters['rfq_id']);
        }

        if (! empty($filters['conversation_id'])) {
            $query->where('conversation_id', $filters['conversation_id']);
        }

        if (! empty($filters['price_min'])) {
            $query->where('total_price', '>=', $filters['price_min']);
        }

        if (! empty($filters['price_max'])) {
            $query->where('total_price', '<=', $filters['price_max']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }
}
