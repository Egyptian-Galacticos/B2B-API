<?php

namespace App\Services;

use App\Models\Rfq;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RfqService
{
    /**
     * Create a new RFQ
     */
    public function create(array $data): Rfq
    {
        if ($data['buyer_id'] === $data['seller_id']) {
            throw new \InvalidArgumentException('Cannot create RFQ to yourself');
        }

        $rfq = Rfq::create([
            'buyer_id'           => $data['buyer_id'],
            'seller_id'          => $data['seller_id'],
            'initial_product_id' => $data['initial_product_id'],
            'initial_quantity'   => $data['initial_quantity'],
            'shipping_country'   => $data['shipping_country'],
            'shipping_address'   => $data['shipping_address'],
            'buyer_message'      => $data['buyer_message'] ?? null,
            'status'             => Rfq::STATUS_PENDING,
        ]);

        return $rfq->load(['buyer.company', 'seller.company', 'initialProduct']);
    }

    /**
     * Get RFQ by ID with access validation
     */
    public function findWithAccess(int $rfqId, int $userId): Rfq
    {
        $rfq = Rfq::findOrFail($rfqId);

        if ($rfq->buyer_id !== $userId && $rfq->seller_id !== $userId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access to this RFQ');
        }

        return $rfq->load(['buyer.company', 'seller.company', 'initialProduct', 'quotes.items.product']);
    }

    /**
     * Update RFQ status
     */
    public function updateStatus(int $rfqId, string $newStatus, int $userId): Rfq
    {
        $rfq = Rfq::findOrFail($rfqId);

        if ($rfq->seller_id !== $userId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only seller can update RFQ status');
        }

        if (! $rfq->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException("Cannot transition to {$newStatus} from current status '{$rfq->status}'");
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
     * Get paginated RFQs with filtering and sorting
     */
    public function getWithFilters(Request $request, ?int $userId = null, ?string $userType = null, int $perPage = 15): LengthAwarePaginator
    {
        $queryHandler = new QueryHandler($request);

        $query = Rfq::with(['buyer.company', 'seller.company', 'initialProduct', 'quotes']);

        if ($userId && $userType) {
            if ($userType === 'buyer') {
                $query->forBuyer($userId);
            } elseif ($userType === 'seller') {
                $query->forSeller($userId);
            }
        } elseif ($userId) {

            $query->where(function ($q) use ($userId) {
                $q->where('buyer_id', $userId)->orWhere('seller_id', $userId);
            });
        }

        $query = $queryHandler
            ->setBaseQuery($query)
            ->setAllowedSorts(['id', 'initial_quantity', 'shipping_country', 'status', 'created_at', 'updated_at', 'buyer.name', 'seller.name', 'initialProduct.name', 'initialProduct.brand', 'initialProduct.price',
            ])
            ->setAllowedFilters(['id', 'initial_quantity', 'shipping_country', 'shipping_address', 'status', 'buyer_id', 'seller_id', 'initial_product_id', 'created_at', 'updated_at', 'buyer.name', 'buyer.email', 'seller.name', 'seller.email', 'initialProduct.name', 'initialProduct.brand', 'initialProduct.model_number',
            ])
            ->apply();

        return $query->paginate($perPage)->withQueryString();
    }
}
