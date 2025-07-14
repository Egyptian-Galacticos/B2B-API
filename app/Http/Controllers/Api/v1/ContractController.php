<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\IndexContractRequest;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Mail\ContractUpdatedByBuyerMail;
use App\Models\Contract;
use App\Models\Quote;
use App\Models\User;
use App\Services\QueryHandler;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContractController extends Controller
{
    use ApiResponse;
    private const PLACEHOLDER_STRING = 'string';
    private const PLACEHOLDER_NULL = 'null';

    public function __construct(private QueryHandler $queryHandler) {}

    /**
     * List contracts
     *
     * Users can only see their own contracts (as buyer or seller)
     * Admins can see all contracts
     * user_type parameter: 'buyer' or 'seller'
     */
    public function index(IndexContractRequest $request): JsonResponse
    {
        try {
            $user = User::findOrFail(Auth::id());
            $perPage = (int) $request->get('size', 15);
            $userType = $request->get('user_type');

            $query = Contract::with(['buyer.company', 'seller.company', 'quote', 'items.product']);

            if ($userType === 'buyer') {
                $query->forBuyer($user->id);
            } elseif ($userType === 'seller') {
                $query->forSeller($user->id);
            } else {
                $query->forUser($user->id);
            }

            $query = $this->queryHandler
                ->setBaseQuery($query)
                ->setAllowedSorts([
                    'id',
                    'contract_number',
                    'status',
                    'total_amount',
                    'contract_date',
                    'estimated_delivery',
                    'created_at',
                ])
                ->setAllowedFilters([
                    'status',
                    'currency',
                    'buyer_id',
                    'seller_id',
                    'contract_date',
                    'total_amount',
                    'quote_id',
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
            $user = User::findOrFail(Auth::id());

            $contract = Contract::findOrFail($id);

            if (! in_array($user->id, [$contract->buyer_id, $contract->seller_id])) {
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
    public function update(string $id, UpdateContractRequest $request): JsonResponse
    {
        try {
            $user = User::findOrFail(Auth::id());

            $contract = Contract::findOrFail($id);

            if (! in_array($user->id, [$contract->buyer_id, $contract->seller_id])) {
                return $this->apiResponseErrors(
                    'Unauthorized',
                    ['You can only update contracts you are involved in'],
                    403
                );
            }

            if ($request->has('status')) {
                $newStatus = $request->status;

                if ($newStatus === Contract::STATUS_APPROVED && $contract->status === Contract::STATUS_PENDING_APPROVAL) {
                    $contract->updateStatus(Contract::STATUS_PENDING_PAYMENT);
                } elseif ($newStatus === Contract::STATUS_IN_PROGRESS) {
                    if (! $user->isAdmin()) {
                        return $this->apiResponseErrors(
                            'Unauthorized',
                            ['Only administrators can set contract status to in_progress'],
                            403
                        );
                    }
                    $contract->updateStatus($newStatus);
                } elseif ($newStatus === Contract::STATUS_DELIVERED) {
                    if ($user->id !== $contract->buyer_id) {
                        return $this->apiResponseErrors(
                            'Unauthorized',
                            ['Only the buyer can confirm delivery'],
                            403
                        );
                    }
                    if ($contract->status !== Contract::STATUS_SHIPPED) {
                        return $this->apiResponseErrors(
                            'Invalid status transition',
                            ['Delivery can only be confirmed when contract is shipped'],
                            400
                        );
                    }
                    $result = $contract->confirmDeliveryByBuyer();
                    if (! $result) {
                        return $this->apiResponseErrors(
                            'Failed to confirm delivery',
                            ['Cannot confirm delivery for current contract status'],
                            400
                        );
                    }
                } elseif ($newStatus === Contract::STATUS_CANCELLED) {
                    if (! $user->canActInRole('buyer', $contract) || $user->id !== $contract->buyer_id) {
                        return $this->apiResponseErrors(
                            'Unauthorized',
                            ['Only the buyer can cancel/reject a contract'],
                            403
                        );
                    }
                    $contract->updateStatus($newStatus);
                } else {
                    if (! $contract->canTransitionTo($newStatus)) {
                        return $this->apiResponseErrors(
                            'Invalid status transition',
                            ["Cannot change from '{$contract->status}' to '{$newStatus}'"],
                            400
                        );
                    }
                    $contract->updateStatus($newStatus);
                }
            }

            $updateData = $request->only([
                'estimated_delivery',
                'shipping_address',
                'billing_address',
                'terms_and_conditions',
                'metadata',
                'buyer_transaction_id',
            ]);

            if ($request->has('shipment_url') && $user->id === $contract->seller_id) {
                if ($contract->status === Contract::STATUS_IN_PROGRESS) {
                    $result = $contract->setShipmentUrl($request->shipment_url);
                    if (! $result) {
                        return $this->apiResponseErrors(
                            'Failed to set shipment URL',
                            ['Cannot set shipment URL for current contract status'],
                            400
                        );
                    }
                } else {
                    return $this->apiResponseErrors(
                        'Invalid operation',
                        ['Shipment URL can only be set when contract is in progress'],
                        400
                    );
                }
            }

            if (! empty($updateData)) {
                $contract->update($updateData);

                if (
                    $request->has('buyer_transaction_id') &&
                    ! empty($request->buyer_transaction_id) &&
                    $contract->status === Contract::STATUS_PENDING_PAYMENT &&
                    $user->id === $contract->buyer_id
                ) {

                    $contract->updateStatus(Contract::STATUS_PENDING_PAYMENT_CONFIRMATION);
                }

                if ($user->id === $contract->buyer_id) {
                    $companyEmail = config('mail.from.address');
                    if ($companyEmail) {
                        $updateType = 'Contract Details Updated';
                        if ($request->has('buyer_transaction_id')) {
                            $updateType = 'Payment Transaction ID Added';
                        }

                        try {
                            $contract->load(['buyer', 'seller']);

                            Mail::to($companyEmail)
                                ->send(new ContractUpdatedByBuyerMail($contract, $updateType));
                        } catch (Exception $mailException) {
                            Log::error('Failed to send contract update email: '.$mailException->getMessage());
                        }
                    }
                }
            }

            return $this->apiResponse(
                new ContractResource($contract->fresh()),
                'Contract updated successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to update contract',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Create a new contract from an accepted quote
     *
     * Seller creates contract with terms and conditions
     */
    public function store(StoreContractRequest $request): JsonResponse
    {
        try {
            $user = User::findOrFail(Auth::id());

            $quote = Quote::with(['rfq', 'items'])->findOrFail($request->quote_id);

            if (! $quote->isAccepted()) {
                return $this->apiResponseErrors(
                    'Invalid quote status',
                    ['Contract can only be created from accepted quotes'],
                    400
                );
            }

            $sellerId = $quote->seller_id ?? $quote->rfq?->seller_id;

            if (! $user->canActInRole('seller', $quote) || ! $sellerId || $sellerId !== $user->id) {
                return $this->apiResponseErrors(
                    'Unauthorized',
                    ['Only the seller can create a contract from their quote'],
                    403
                );
            }

            $existingContract = Contract::where('quote_id', $quote->id)->first();
            if ($existingContract) {
                return $this->apiResponseErrors(
                    'Contract already exists',
                    ['A contract has already been created for this quote'],
                    400
                );
            }

            $buyerId = $quote->buyer_id ?? $quote->rfq?->buyer_id;
            if (! $buyerId) {
                return $this->apiResponseErrors(
                    'Invalid quote',
                    ['Quote must have a valid buyer'],
                    400
                );
            }

            $contractNumber = 'CON-'.date('Y').'-'.str_pad(Contract::count() + 1, 6, '0', STR_PAD_LEFT);

            $buyer = User::with('company')->find($buyerId);

            $parseAddress = function ($address) {
                if (is_array($address)) {
                    $hasRealData = false;
                    foreach ($address as $field => $value) {
                        if (! empty($value) && $value !== self::PLACEHOLDER_STRING && $value !== self::PLACEHOLDER_NULL) {
                            $hasRealData = true;
                            break;
                        }
                    }

                    return $hasRealData ? $address : null;
                }
                if (is_string($address) && ! empty($address) && $address !== self::PLACEHOLDER_STRING) {
                    return [
                        'street'      => $address,
                        'city'        => '',
                        'state'       => '',
                        'postal_code' => '',
                        'country'     => '',
                    ];
                }

                return null;
            };

            // shipping address priority:request > rfq > buyer's company
            $shippingAddress = null;
            if ($request->has('shipping_address')) {
                $shippingAddress = $parseAddress($request->shipping_address);
            }
            if (! $shippingAddress && ! empty($quote->rfq?->shipping_address)) {
                $shippingAddress = $parseAddress($quote->rfq->shipping_address);
            }
            if (! $shippingAddress && ! empty($buyer?->company?->address)) {
                $shippingAddress = $buyer->company->address;
            }

            // billing address priority:request > buyer's company
            $billingAddress = null;
            if ($request->has('billing_address')) {
                $billingAddress = $parseAddress($request->billing_address);
            }
            if (! $billingAddress && ! empty($buyer?->company?->address)) {
                $billingAddress = $buyer->company->address;
            }

            $shippingAddress = $shippingAddress ?: [
                'street'      => 'Address not provided',
                'city'        => '',
                'state'       => '',
                'postal_code' => '',
                'country'     => '',
            ];

            $billingAddress = $billingAddress ?: [
                'street'      => 'Address not provided',
                'city'        => '',
                'state'       => '',
                'postal_code' => '',
                'country'     => '',
            ];

            $metadata = [];
            if ($request->has('metadata') && is_array($request->metadata)) {
                $metadata = $request->metadata;
            } elseif ($request->has('metadata') && ! is_null($request->metadata)) {
                $metadata = ['note' => $request->metadata];
            }

            $contract = Contract::create([
                'quote_id'             => $quote->id,
                'contract_number'      => $contractNumber,
                'buyer_id'             => $buyerId,
                'seller_id'            => $user->id,
                'status'               => Contract::STATUS_PENDING_APPROVAL,
                'total_amount'         => $quote->total_price,
                'currency'             => 'USD',
                'contract_date'        => now(),
                'estimated_delivery'   => $request->estimated_delivery,
                'shipping_address'     => $shippingAddress,
                'billing_address'      => $billingAddress,
                'terms_and_conditions' => $request->terms_and_conditions,
                'metadata'             => $metadata,
            ]);

            foreach ($quote->items as $quoteItem) {
                $contract->items()->create([
                    'product_id'     => $quoteItem->product_id,
                    'quantity'       => $quoteItem->quantity,
                    'unit_price'     => $quoteItem->unit_price,
                    'total_price'    => $quoteItem->total_price,
                    'specifications' => $quoteItem->specifications,
                ]);
            }

            $contract->load([
                'buyer.company',
                'seller.company',
                'quote',
                'items.product',
            ]);

            return $this->apiResponse(
                new ContractResource($contract),
                'Contract created successfully and sent to buyer for approval',
                201
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to create contract',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
