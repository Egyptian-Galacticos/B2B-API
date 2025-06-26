<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\User;
use App\Services\QuoteService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class QuoteController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly QuoteService $quoteService
    ) {}

    /**
     * List quotes
     *
     * - Admin: See all quotes in the system
     * - Regular users: See all their quotes (both as buyer and seller)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            assert($user instanceof User);
            $perPage = (int) $request->get('size', 15);

            if ($user->isAdmin()) {
                $quotes = $this->quoteService->getWithFilters($request, null, $perPage);
            } else {
                $quotes = $this->quoteService->getWithFilters($request, $user->id, $perPage);
            }

            return $this->apiResponse(
                QuoteResource::collection($quotes),
                'Quotes retrieved successfully',
                200,
                $this->getPaginationMeta($quotes)
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to retrieve quotes', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created quote
     *
     * This method allows sellers to create a quote in response to an RFQ or conversation.
     */
    public function store(CreateQuoteRequest $request): JsonResponse
    {
        try {
            $quote = $this->quoteService->create([
                'rfq_id'          => $request->rfq_id,
                'conversation_id' => $request->conversation_id,
                'seller_message'  => $request->seller_message,
                'items'           => $request->items,
            ], Auth::id());

            $message = $quote->rfq
                ? 'Quote created and sent successfully, RFQ marked as quoted'
                : 'Quote created from conversation successfully';

            return $this->apiResponse(
                new QuoteResource($quote),
                $message,
                201
            );
        } catch (InvalidArgumentException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 400);
        } catch (AuthorizationException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 403);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('Resource not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to create quote', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified quote
     */
    public function show(int $id): JsonResponse
    {
        try {
            $quote = $this->quoteService->findWithAccess($id, Auth::id());

            return $this->apiResponse(
                new QuoteResource($quote),
                'Quote retrieved successfully'
            );
        } catch (AuthorizationException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 403);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('Quote not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to retrieve quote', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing quote
     *
     * This method allows sellers to update the status and items of a quote
     */
    public function update(UpdateQuoteRequest $request, int $id): JsonResponse
    {
        try {
            $userRoles = Auth::user()->roles->pluck('name')->toArray();

            $quote = $this->quoteService->update($id, [
                'status'         => $request->status,
                'items'          => $request->items,
                'seller_message' => $request->seller_message,
            ], Auth::id(), $userRoles);

            $message = $this->quoteService->getStatusMessage($quote->status);

            return $this->apiResponse(
                new QuoteResource($quote),
                $message
            );
        } catch (InvalidArgumentException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 400);
        } catch (AuthorizationException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 403);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('Quote not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to update quote', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified quote from storage
     *
     * This method allows sellers to delete a quote they created.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->quoteService->delete($id, Auth::id());

            return $this->apiResponse(
                null,
                'Quote deleted successfully'
            );
        } catch (AuthorizationException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 403);
        } catch (InvalidArgumentException $e) {
            return $this->apiResponseErrors($e->getMessage(), [], 422);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors('Quote not found', [], 404);
        } catch (Exception $e) {
            return $this->apiResponseErrors('Failed to delete quote', ['error' => $e->getMessage()], 500);
        }
    }
}
