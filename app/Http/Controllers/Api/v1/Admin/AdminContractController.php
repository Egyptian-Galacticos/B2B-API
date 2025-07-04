<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Contract\AdminContractFilterRequest;
use App\Http\Requests\Admin\Contract\BulkContractActionRequest;
use App\Http\Requests\Admin\Contract\UpdateContractStatusRequest;
use App\Http\Resources\Admin\AdminContractResource;
use App\Services\Admin\ContractService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class AdminContractController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ContractService $contractService
    ) {}

    /**
     * Display a listing of all contracts for admin oversight.
     *
     * @authenticated
     */
    public function index(AdminContractFilterRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $contracts = $this->contractService->getAllContractsWithFilters($filters, $request);

            return $this->apiResponse(
                AdminContractResource::collection($contracts),
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
     * Display the specified contract for admin oversight.
     *
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        try {
            $contract = $this->contractService->getContractById($id);

            return $this->apiResponse(
                new AdminContractResource($contract),
                'Contract retrieved successfully',
                200
            );
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('Contract not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve contract',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update contract status for administrative purposes.
     *
     * @authenticated
     */
    public function updateStatus(UpdateContractStatusRequest $request, int $id): JsonResponse
    {
        try {
            $contract = $this->contractService->updateContractStatus($id, $request->status);
            $message = $this->contractService->getStatusMessage($request->status);

            return $this->apiResponse(
                new AdminContractResource($contract),
                $message,
                200
            );
        } catch (InvalidArgumentException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 400);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('Contract not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to update contract status',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Handle bulk actions on contracts for admin oversight.
     *
     * @authenticated
     */
    public function bulkAction(BulkContractActionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $contractIds = $validated['contract_ids'];
            $action = $validated['action'];
            $status = $validated['status'] ?? null;

            $result = $this->contractService->bulkActionContracts($contractIds, $action, $status);

            if (! $result['success']) {
                return $this->apiResponseErrors(
                    $result['message'],
                    $result['data'] ?? [],
                    $result['status']
                );
            }

            return $this->apiResponse(
                $result['data'] ?? null,
                $result['message'],
                200
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to perform bulk action',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
