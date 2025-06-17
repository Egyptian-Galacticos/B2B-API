<?php

namespace App\Services;

use App\Models\Rfq;
use Illuminate\Pagination\LengthAwarePaginator;

class RfqService
{
    /**
     * Get paginated RFQs for a seller
     */
    public function getForSeller(int $sellerId, int $perPage = 15): LengthAwarePaginator
    {
        return Rfq::forSeller($sellerId)
            ->with(['buyer', 'seller', 'initialProduct', 'quotes'])
            ->latest()
            ->paginate($perPage);
    }

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

        return $rfq->load(['buyer', 'seller', 'initialProduct']);
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

        return $rfq->load(['buyer', 'seller', 'initialProduct', 'quotes.items.product']);
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

        return $rfq->load(['buyer', 'seller', 'initialProduct', 'quotes']);
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
            default                 => 'RFQ updated successfully'
        };
    }
}
