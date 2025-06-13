<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRfqRequest;
use App\Http\Resources\RfqResource;
use App\Models\Rfq;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RfqController extends Controller
{
    use ApiResponse;

    /**
     * List RFQs for sellers
     *
     * This method retrieves a list of RFQs (Request for Quotations) available for sellers.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            $query = Rfq::forSeller($currentUser->id)
                ->with(['buyer', 'seller', 'initialProduct', 'quotes'])
                ->latest();

            $rfqs = $query->paginate(15);
            $meta = [
                'page'       => $rfqs->currentPage(),
                'limit'      => $rfqs->perPage(),
                'total'      => $rfqs->total(),
                'totalPages' => $rfqs->lastPage(),
            ];

            return $this->apiResponse(
                RfqResource::collection($rfqs->items()),
                'RFQs retrieved successfully',
                200,
                $meta
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to retrieve RFQs', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new RFQ (for buyers)
     *
     * This method allows a buyer to create a new Request for Quotation (RFQ).
     */
    // shouldn't be able to create rfq from my self
    public function store(CreateRfqRequest $request): JsonResponse
    {
        try {
            if ($request->seller_id == Auth::id()) {
                return $this->apiResponseErrors(
                    'Cannot create RFQ to yourself',
                    [],
                    400
                );
            }
            $rfq = Rfq::create([
                'buyer_id'           => Auth::id(),
                'seller_id'          => $request->seller_id,
                'initial_product_id' => $request->initial_product_id,
                'initial_quantity'   => $request->initial_quantity,
                'shipping_country'   => $request->shipping_country,
                'shipping_address'   => $request->shipping_address,
                'buyer_message'      => $request->buyer_message,
                'status'             => Rfq::STATUS_PENDING,
            ]);

            $rfq->load(['buyer', 'seller', 'initialProduct']);

            return $this->apiResponse(
                new RfqResource($rfq),
                'RFQ created successfully',
                201
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to create RFQ', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show specific RFQ
     *
     * This method retrieves a specific RFQ by its ID.
     */
    public function show(Rfq $rfq): JsonResponse
    {
        try {
            if ($rfq->buyer_id !== Auth::id() && $rfq->seller_id !== Auth::id()) {
                return $this->apiResponseErrors('Unauthorized access to this RFQ', [], 403);
            }

            $rfq->load(['buyer', 'seller', 'initialProduct', 'quotes.items.product']);

            return $this->apiResponse(
                new RfqResource($rfq),
                'RFQ retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->apiResponseErrors('Failed to retrieve RFQ', [], 500);
        }
    }

    /**
     * Mark RFQ as in progress (when seller opens it)
     *
     * This method marks an RFQ as in progress when the seller opens it.
     */
    public function markInProgress(Rfq $rfq): JsonResponse
    {
        try {
            if ($rfq->seller_id !== Auth::id()) {
                return $this->apiResponseErrors('Unauthorized action', [], 403);
            }

            if (! $rfq->isPending()) {
                return $this->apiResponseErrors('RFQ is not in pending status', [], 400);
            }

            $rfq->transitionTo(Rfq::STATUS_IN_PROGRESS);
            $rfq->load(['buyer', 'seller', 'initialProduct']);

            return $this->apiResponse(
                new RfqResource($rfq),
                'RFQ marked as in progress'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to update RFQ status', [$e->getMessage()], 500);
        }
    }

    /**
     * Mark RFQ as seen (when seller views it)
     *
     * This method marks an RFQ as seen when the seller views it.
     */
    public function markSeen(Rfq $rfq): JsonResponse
    {
        try {
            if ($rfq->seller_id !== Auth::id()) {
                return $this->apiResponseErrors('Unauthorized action', [], 403);
            }

            if (! $rfq->isPending()) {
                return $this->apiResponseErrors('RFQ is not in pending status', [], 400);
            }

            $rfq->transitionTo(Rfq::STATUS_SEEN);
            $rfq->load(['buyer', 'seller', 'initialProduct']);

            return $this->apiResponse(
                new RfqResource($rfq),
                'RFQ marked as seen'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to update RFQ status', [$e->getMessage()], 500);
        }
    }

    /**
     * Reject an RFQ
     *
     * This method rejects an RFQ.
     */
    public function reject(Rfq $rfq): JsonResponse
    {
        try {
            if ($rfq->seller_id !== Auth::id()) {
                return $this->apiResponseErrors('Unauthorized action', [], 403);
            }

            if (! $rfq->canTransitionTo(Rfq::STATUS_REJECTED)) {
                return $this->apiResponseErrors('Cannot reject RFQ in current status', [], 400);
            }

            $rfq->transitionTo(Rfq::STATUS_REJECTED);
            $rfq->load(['buyer', 'seller', 'initialProduct']);

            return $this->apiResponse(
                new RfqResource($rfq),
                'RFQ rejected successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to reject RFQ', [$e->getMessage()], 500);
        }
    }

    /**
     * Accept an RFQ (buyer action)
     *
     * This method allows a buyer to accept an RFQ that has been quoted by the seller.
     */
    public function accept(Rfq $rfq): JsonResponse
    {
        try {
            if ($rfq->buyer_id !== Auth::id()) {
                return $this->apiResponseErrors('Unauthorized action', [], 403);
            }

            if (! $rfq->isQuoted()) {
                return $this->apiResponseErrors('RFQ must be quoted to accept', [], 400);
            }

            $rfq->transitionTo(Rfq::STATUS_ACCEPTED);
            $rfq->load(['buyer', 'seller', 'initialProduct', 'quotes']);

            return $this->apiResponse(
                new RfqResource($rfq),
                'RFQ accepted successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to accept RFQ', [$e->getMessage()], 500);
        }
    }

    /**
     * Close an RFQ (after completion)
     *
     * This method allows a buyer or seller to close an RFQ that has been accepted.
     */
    public function close(Rfq $rfq): JsonResponse
    {
        try {
            if ($rfq->buyer_id !== Auth::id() && $rfq->seller_id !== Auth::id()) {
                return $this->apiResponseErrors('Unauthorized action', [], 403);
            }

            if (! $rfq->isAccepted()) {
                return $this->apiResponseErrors('Only accepted RFQs can be closed', [], 400);
            }

            $rfq->transitionTo(Rfq::STATUS_CLOSED);
            $rfq->load(['buyer', 'seller', 'initialProduct', 'quotes']);

            return $this->apiResponse(
                new RfqResource($rfq),
                'RFQ closed successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to close RFQ', [$e->getMessage()], 500);
        }
    }
}
