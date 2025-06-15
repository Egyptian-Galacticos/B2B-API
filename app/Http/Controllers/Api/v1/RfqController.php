<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRfqRequest;
use App\Http\Requests\UpdateRfqRequest;
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
                RfqResource::collection($rfqs),
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
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to retrieve RFQ', [], 500);
        }
    }

    /**
     * Update RFQ status
     *
     * This method handles seller-initiated status transitions for an RFQ.
     * Only sellers can update RFQ status
     */
    public function update(UpdateRfqRequest $request, int $id): JsonResponse
    {
        try {
            $rfq = Rfq::find($id);
            if (! $rfq) {
                return $this->apiResponseErrors('RFQ not found', [], 404);
            }
            $newStatus = $request->status;
            $currentUser = Auth::user();

            switch ($newStatus) {
                case Rfq::STATUS_SEEN:
                    if ($rfq->seller_id !== $currentUser->id) {
                        return $this->apiResponseErrors('Only seller can mark RFQ as seen', [], 403);
                    }
                    if (! $rfq->isPending()) {
                        return $this->apiResponseErrors('RFQ is not in pending status', [], 400);
                    }
                    break;

                case Rfq::STATUS_IN_PROGRESS:
                    if ($rfq->seller_id !== $currentUser->id) {
                        return $this->apiResponseErrors('Only seller can mark RFQ as in progress', [], 403);
                    }
                    if (! in_array($rfq->status, [Rfq::STATUS_PENDING, Rfq::STATUS_SEEN])) {
                        return $this->apiResponseErrors('RFQ must be in pending or seen status to mark as in progress', [], 400);
                    }
                    break;

                case Rfq::STATUS_QUOTED:
                    if ($rfq->seller_id !== $currentUser->id) {
                        return $this->apiResponseErrors('Only seller can mark RFQ as quoted', [], 403);
                    }
                    if (! $rfq->canTransitionTo(Rfq::STATUS_QUOTED)) {
                        return $this->apiResponseErrors('Cannot mark RFQ as quoted from current status', [], 400);
                    }
                    break;

                case Rfq::STATUS_REJECTED:
                    if ($rfq->seller_id !== $currentUser->id) {
                        return $this->apiResponseErrors('Only seller can reject RFQ', [], 403);
                    }
                    if (! $rfq->canTransitionTo(Rfq::STATUS_REJECTED)) {
                        return $this->apiResponseErrors('Cannot reject RFQ in current status', [], 400);
                    }
                    break;

                case Rfq::STATUS_CLOSED:
                    if ($rfq->buyer_id !== $currentUser->id && $rfq->seller_id !== $currentUser->id) {
                        return $this->apiResponseErrors('Unauthorized access to this RFQ', [], 403);
                    }
                    if (! $rfq->isAccepted()) {
                        return $this->apiResponseErrors('Only accepted RFQs can be closed', [], 400);
                    }
                    break;

                default:
                    return $this->apiResponseErrors('Invalid status transition', [], 400);
            }

            $rfq->update(['status' => $newStatus]);
            $rfq->load(['buyer', 'seller', 'initialProduct', 'quotes']);

            $statusMessages = [
                Rfq::STATUS_SEEN        => 'RFQ marked as seen',
                Rfq::STATUS_IN_PROGRESS => 'RFQ marked as in progress',
                Rfq::STATUS_QUOTED      => 'RFQ marked as quoted',
                Rfq::STATUS_REJECTED    => 'RFQ rejected successfully',
                Rfq::STATUS_CLOSED      => 'RFQ closed successfully',
            ];

            return $this->apiResponse(
                new RfqResource($rfq),
                $statusMessages[$newStatus] ?? 'RFQ updated successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to update RFQ', ['error' => $e->getMessage()], 500);
        }
    }
}
