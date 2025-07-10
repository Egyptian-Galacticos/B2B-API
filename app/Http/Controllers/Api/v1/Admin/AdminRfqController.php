<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Rfq\AdminRfqFilterRequest;
use App\Http\Requests\Admin\Rfq\BulkRfqActionRequest;
use App\Http\Requests\Admin\Rfq\UpdateRfqStatusRequest;
use App\Http\Resources\Admin\AdminRfqResource;
use App\Services\Admin\RfqService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class AdminRfqController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RfqService $rfqService
    ) {}

    /**
     * Display a listing of all RFQs for admin oversight.
     *
     * @authenticated
     */
    public function index(AdminRfqFilterRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $result = $this->rfqService->getAllRfqsWithFilters($filters, $request);

            $rfqs = $result['rfqs'];
            $statistics = $result['statistics'];

            $paginationMeta = $this->getPaginationMeta($rfqs);
            $meta = array_merge($paginationMeta, $statistics);

            return $this->apiResponse(
                AdminRfqResource::collection($rfqs),
                'RFQs retrieved successfully',
                200,
                $meta
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve RFQs',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified RFQ for admin oversight.
     *
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        try {
            $rfq = $this->rfqService->getRfqById($id);

            return $this->apiResponse(
                new AdminRfqResource($rfq),
                'RFQ retrieved successfully',
                200
            );
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('RFQ not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve RFQ',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update RFQ status for administrative purposes.
     *
     * @authenticated
     */
    public function updateStatus(UpdateRfqStatusRequest $request, int $id): JsonResponse
    {
        try {
            $rfq = $this->rfqService->updateRfqStatus($id, $request->status);
            $message = $this->rfqService->getStatusMessage($request->status);

            return $this->apiResponse(
                new AdminRfqResource($rfq),
                $message,
                200
            );
        } catch (InvalidArgumentException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 400);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('RFQ not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to update RFQ status',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Handle bulk actions on RFQs for admin oversight.
     *
     * @authenticated
     */
    public function bulkAction(BulkRfqActionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $rfqIds = $validated['rfq_ids'];
            $action = $validated['action'];
            $status = $validated['status'] ?? null;

            $result = $this->rfqService->bulkActionRfqs($rfqIds, $action, $status);

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
