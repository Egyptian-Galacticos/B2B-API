<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rfq\CreateRfqRequest;
use App\Http\Requests\Rfq\IndexRfqRequest;
use App\Http\Requests\Rfq\UpdateRfqRequest;
use App\Http\Resources\RfqResource;
use App\Models\User;
use App\Notifications\NewRfqCreatedNotification;
use App\Services\RfqService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class RfqController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RfqService $rfqService
    ) {}

    /**
     * List RFQs
     *
     * - Admin : See all RFQs in the system
     * - Regular users: See all their RFQs (both as buyer and seller)
     * - user_type parameter: 'buyer' or 'seller'
     */
    public function index(IndexRfqRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            assert($user instanceof User);
            $perPage = (int) $request->get('size', 15);
            $userType = $request->get('user_type');

            if ($user->isAdmin()) {
                $results = $this->rfqService->getWithFilters($request, null, null, $perPage);
            } else {
                $results = $this->rfqService->getWithFilters($request, $user->id, $userType, $perPage);
            }

            $paginationMeta = $this->getPaginationMeta($results['rfqs']);

            return $this->apiResponse(
                RfqResource::collection($results['rfqs']),
                'RFQs retrieved successfully',
                200,
                array_merge($paginationMeta, $results['statistics'])
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to retrieve RFQs', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new RFQ (for buyers)
     *
     * This method allows buyers to create a new RFQ by specifying the seller, product, quantity, and shipping details.
     */
    public function store(CreateRfqRequest $request): JsonResponse
    {
        try {
            $rfq = $this->rfqService->create([
                'buyer_id'           => Auth::id(),
                'seller_id'          => $request->seller_id,
                'initial_product_id' => $request->initial_product_id,
                'initial_quantity'   => $request->initial_quantity,
                'shipping_country'   => $request->shipping_country,
                'shipping_address'   => $request->shipping_address,
                'buyer_message'      => $request->buyer_message,
            ]);
            if ($rfq->seller) {
                $rfq->seller->notify(new NewRfqCreatedNotification($rfq));
            }

            return $this->apiResponse(
                new RfqResource($rfq),
                'RFQ created successfully',
                201
            );
        } catch (InvalidArgumentException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 400);
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to create RFQ', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show specific RFQ
     *
     * This method retrieves a specific RFQ by its ID, ensuring the authenticated user has access to it.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $rfq = $this->rfqService->findWithAccess($id, Auth::id());

            return $this->apiResponse(
                new RfqResource($rfq),
                'RFQ retrieved successfully'
            );
        } catch (AuthorizationException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 403);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('RFQ not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to retrieve RFQ', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update RFQ status
     *
     * this method allows sellers to update the status of an RFQ.
     */
    public function update(UpdateRfqRequest $request, int $id): JsonResponse
    {
        try {
            $rfq = $this->rfqService->updateStatus($id, $request->status, Auth::id());
            $message = $this->rfqService->getStatusMessage($request->status);

            return $this->apiResponse(
                new RfqResource($rfq),
                $message
            );
        } catch (AuthorizationException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 403);
        } catch (InvalidArgumentException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 400);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('RFQ not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to update RFQ', ['error' => $e->getMessage()], 500);
        }
    }
}
