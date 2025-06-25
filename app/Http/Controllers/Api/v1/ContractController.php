<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Services\QueryHandler;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    use ApiResponse;

    public function __construct(private QueryHandler $queryHandler) {}

    /**
     * List contracts
     *
     * Users can only see their own contracts (as buyer or seller)
     * Admins can see all contracts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = (int) $request->get('size', 15);

            $query = Contract::with(['buyer', 'seller', 'quote', 'items.product']);
            if (! $user->isAdmin()) {
                $query->forUser($user->id);
            }

            $query = $this->queryHandler
                ->setBaseQuery($query)
                ->setAllowedSorts([
                    'id', 'contract_number', 'status', 'total_amount',
                    'contract_date', 'estimated_delivery', 'created_at',
                ])
                ->setAllowedFilters([
                    'status', 'currency', 'buyer_id', 'seller_id',
                    'contract_date', 'total_amount', 'quote_id',
                ])
                ->apply();

            $contracts = $query->paginate($perPage);

            return $this->apiResponse(
                ContractResource::collection($contracts),
                'Contracts retrieved successfully',
                200,
                $this->getPaginationMeta($contracts)
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve contracts',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Show specific contract
     *
     * Users can only view contracts they're involved in
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $contract = Contract::findOrFail($id);

            if (! $user->isAdmin() && ! in_array($user->id, [$contract->buyer_id, $contract->seller_id])) {
                return $this->apiResponseErrors(
                    'Unauthorized',
                    ['You can only view contracts you are involved in'],
                    403
                );
            }

            $contract->load([
                'buyer.company',
                'seller.company',
                'quote',
                'items.product',
            ]);

            return $this->apiResponse(
                new ContractResource($contract),
                'Contract retrieved successfully'
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve contract',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update contract
     *
     * Both buyer and seller can update contract within allowed parameters
     */
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $contract = Contract::findOrFail($id);

            if (! $user->isAdmin() && ! in_array($user->id, [$contract->buyer_id, $contract->seller_id])) {
                return $this->apiResponseErrors(
                    'Unauthorized',
                    ['You can only update contracts you are involved in'],
                    403
                );
            }

            if ($request->has('status')) {
                $request->validate([
                    'status' => [
                        'required',
                        'string',
                        Rule::in(Contract::VALID_STATUSES),
                    ],
                ]);

                $newStatus = $request->status;

                if (! $contract->canTransitionTo($newStatus)) {
                    return $this->apiResponseErrors(
                        'Invalid status transition',
                        ["Cannot change from '{$contract->status}' to '{$newStatus}'"],
                        400
                    );
                }

                $contract->updateStatus($newStatus);

                return $this->apiResponse(
                    new ContractResource($contract->fresh()),
                    'Contract status updated successfully'
                );
            }

            return $this->apiResponseErrors(
                'No valid updates provided',
                ['Only status updates are currently supported'],
                400
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to update contract',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
