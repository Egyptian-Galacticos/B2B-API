<?php

namespace App\Services\Admin;

use App\Http\Resources\Admin\AdminRfqResource;
use App\Models\Rfq;
use App\Services\QueryHandler;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RfqService
{
    /**
     * Get all RFQs with filtering and pagination for admin oversight.
     */
    public function getAllRfqsWithFilters(array $filters, Request $request): LengthAwarePaginator
    {
        $query = Rfq::with([
            'buyer.company',
            'seller.company',
            'initialProduct',
            'quotes',
        ]);

        $queryHandler = new QueryHandler($request);
        $queryHandler->setBaseQuery($query)
            ->setAllowedSorts([
                'id',
                'initial_quantity',
                'shipping_country',
                'status',
                'created_at',
                'updated_at',
                'buyer.name',
                'seller.name',
                'initialProduct.name',
                'initialProduct.brand',
            ])
            ->setAllowedFilters([
                'id',
                'status',
                'buyer_id',
                'seller_id',
                'initial_product_id',
                'shipping_country',
                'created_at',
                'updated_at',
                'buyer.name',
                'buyer.email',
                'seller.name',
                'seller.email',
                'initialProduct.name',
                'initialProduct.brand',
            ]);

        $this->applyCustomFilters($query, $filters);

        $filteredQuery = $queryHandler->apply();

        return $filteredQuery->paginate($request->per_page ?? 15);
    }

    /**
     * Get RFQ by ID for admin oversight.
     */
    public function getRfqById(int $id): Rfq
    {
        return Rfq::with([
            'buyer.company',
            'seller.company',
            'initialProduct',
            'quotes.items.product',
        ])->findOrFail($id);
    }

    /**
     * Update RFQ status for admin oversight.
     */
    public function updateRfqStatus(int $id, string $newStatus): Rfq
    {
        $rfq = Rfq::findOrFail($id);

        if (! $rfq->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException("Cannot transition to {$newStatus} from current status '{$rfq->status}'");
        }

        $rfq->update(['status' => $newStatus]);

        return $rfq->load(['buyer.company', 'seller.company', 'initialProduct', 'quotes']);
    }

    /**
     * Get success message for status update
     */
    public function getStatusMessage(string $status): string
    {
        return match ($status) {
            Rfq::STATUS_SEEN        => 'RFQ marked as seen',
            Rfq::STATUS_IN_PROGRESS => 'RFQ marked as in progress',
            Rfq::STATUS_QUOTED      => 'RFQ marked as quoted',
            Rfq::STATUS_REJECTED    => 'RFQ marked as rejected',
            default                 => 'RFQ updated successfully'
        };
    }

    /**
     * Handle bulk actions on RFQs.
     */
    public function bulkActionRfqs(array $rfqIds, string $action, ?string $status = null): array
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

            $rfqs = Rfq::whereIn('id', $rfqIds)->get();

            if ($rfqs->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No RFQs found for the provided IDs',
                    'status'  => 404,
                ];
            }

            $successCount = 0;
            $errors = [];
            $updatedRfqs = [];

            foreach ($rfqs as $rfq) {
                try {
                    switch ($action) {
                        case 'delete':
                            $rfq->delete();
                            $successCount++;
                            break;
                        case 'update_status':
                            if (! $rfq->canTransitionTo($status)) {
                                $errors[] = "RFQ {$rfq->id}: Cannot transition from {$rfq->status} to {$status}";
                            } else {
                                $rfq->update(['status' => $status]);
                                $rfq->load(['buyer.company', 'seller.company', 'initialProduct', 'quotes']);
                                $updatedRfqs[] = $rfq;
                                $successCount++;
                            }
                            break;
                    }
                } catch (Exception $e) {
                    $errors[] = "RFQ {$rfq->id}: ".$e->getMessage();
                }
            }

            $message = $successCount > 0
                ? "Bulk action completed successfully on {$successCount} RFQs"
                : 'Bulk action failed';

            $responseData = [];

            if ($action === 'update_status' && ! empty($updatedRfqs)) {
                $responseData['rfqs'] = AdminRfqResource::collection($updatedRfqs);
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
     * Apply custom filters for RFQ queries.
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

        if (! empty($filters['shipping_country'])) {
            $query->where('shipping_country', 'like', '%'.$filters['shipping_country'].'%');
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['quantity_min'])) {
            $query->where('initial_quantity', '>=', $filters['quantity_min']);
        }

        if (! empty($filters['quantity_max'])) {
            $query->where('initial_quantity', '<=', $filters['quantity_max']);
        }
    }
}
