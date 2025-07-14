<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quote\AdminQuoteFilterRequest;
use App\Http\Requests\Admin\Quote\BulkQuoteActionRequest;
use App\Http\Requests\Admin\Quote\UpdateQuoteStatusRequest;
use App\Http\Resources\Admin\AdminQuoteResource;
use App\Services\Admin\QuoteService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class AdminQuoteController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly QuoteService $quoteService
    ) {}

    /**
     * Display a listing of all quotes for admin oversight.
     *
     * @authenticated
     */
    public function index(AdminQuoteFilterRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $quotes = $this->quoteService->getAllQuotesWithFilters($filters, $request);

            return $this->apiResponse(
                AdminQuoteResource::collection($quotes),
                'Quotes retrieved successfully',
                200,
                $this->getPaginationMeta($quotes)
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve quotes',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified quote for admin oversight.
     *
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        try {
            $quote = $this->quoteService->getQuoteById($id);

            return $this->apiResponse(
                new AdminQuoteResource($quote),
                'Quote retrieved successfully',
                200
            );
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('Quote not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve quote',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update quote status for administrative purposes.
     *
     * @authenticated
     */
    public function updateStatus(UpdateQuoteStatusRequest $request, int $id): JsonResponse
    {
        try {
            $quote = $this->quoteService->updateQuoteStatus($id, $request->status);
            $message = $this->quoteService->getStatusMessage($request->status);

            return $this->apiResponse(
                new AdminQuoteResource($quote),
                $message,
                200
            );
        } catch (InvalidArgumentException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 400);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('Quote not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to update quote status',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Handle bulk actions on quotes for admin oversight.
     *
     * @authenticated
     */
    public function bulkAction(BulkQuoteActionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $quoteIds = $validated['quote_ids'];
            $action = $validated['action'];
            $status = $validated['status'] ?? null;

            $result = $this->quoteService->bulkActionQuotes($quoteIds, $action, $status);

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
