<?php

namespace App\Services\Admin;

use App\Http\Resources\Admin\AdminContractResource;
use App\Models\Contract;
use App\Notifications\ContractInProgressNotification;
use App\Notifications\ContractStatusChangedNotification;
use App\Notifications\PaymentMadeToSellerNotification;
use App\Services\QueryHandler;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request; // New notification
use InvalidArgumentException;

class ContractService
{
    /**
     * Get all contracts with filtering and pagination for admin oversight.
     */
    public function getAllContractsWithFilters(array $filters, Request $request): LengthAwarePaginator
    {
        $query = Contract::with([
            'buyer.company',
            'seller.company',
            'quote.rfq.initialProduct',
            'items.product',
        ]);

        $queryHandler = new QueryHandler($request);
        $queryHandler->setBaseQuery($query)
            ->setAllowedSorts([
                'id',
                'contract_number',
                'total_amount',
                'status',
                'contract_date',
                'estimated_delivery',
                'created_at',
                'updated_at',
                'buyer.name',
                'seller.name',
            ])
            ->setAllowedFilters([
                'id',
                'contract_number',
                'total_amount',
                'status',
                'buyer_id',
                'seller_id',
                'quote_id',
                'currency',
                'contract_date',
                'estimated_delivery',
                'created_at',
                'updated_at',
                'buyer.name',
                'buyer.email',
                'seller.name',
                'seller.email',
            ]);

        $this->applyCustomFilters($query, $filters);

        $filteredQuery = $queryHandler->apply();

        return $filteredQuery->paginate($request->size ?? 10);
    }

    /**
     * Get contract by ID for admin oversight.
     */
    public function getContractById(int $id): Contract
    {
        return Contract::with([
            'buyer.company',
            'seller.company',
            'quote.rfq.initialProduct',
            'items.product',
        ])->findOrFail($id);
    }

    /**
     * Update contract status for admin oversight.
     */
    public function updateContractStatus(int $id, string $newStatus, ?string $sellerTransactionId = null): Contract
    {
        $contract = Contract::findOrFail($id);

        if ($newStatus === Contract::STATUS_APPROVED && $contract->status === Contract::STATUS_PENDING_APPROVAL) {
            $finalStatus = Contract::STATUS_PENDING_PAYMENT;
        } elseif ($newStatus === Contract::STATUS_SHIPPED && $contract->status === Contract::STATUS_VERIFY_SHIPMENT_URL) {
            $result = $contract->verifyShipmentUrlByAdmin();
            if (! $result) {
                throw new InvalidArgumentException("Cannot verify shipment URL for contract in status {$contract->status}");
            }
            $finalStatus = Contract::STATUS_SHIPPED;
        } else {
            $finalStatus = $newStatus;
        }

        if ($finalStatus !== $contract->status && ! $contract->canTransitionTo($finalStatus)) {
            throw new InvalidArgumentException("Cannot transition from {$contract->status} to {$finalStatus}");
        }
        $updateData = ['status' => $finalStatus];

        if ($finalStatus === Contract::STATUS_DELIVERED_AND_PAID && $sellerTransactionId) {
            $updateData['seller_transaction_id'] = $sellerTransactionId;
        }

        // Capture original status to check if a change occurred
        $originalStatus = $contract->status;

        // Perform the update
        if ($newStatus !== Contract::STATUS_SHIPPED || $contract->status !== Contract::STATUS_VERIFY_SHIPMENT_URL) {
            $contract->update($updateData);
        }

        $contract->load(['buyer', 'seller']); // Ensure buyer and seller are loaded for all notifications

        // Dispatch existing notifications
        if ($finalStatus === Contract::STATUS_IN_PROGRESS) {
            $contract->seller->notify(new ContractInProgressNotification($contract));
        } elseif ($finalStatus === Contract::STATUS_DELIVERED_AND_PAID) {
            $contract->seller->notify(new PaymentMadeToSellerNotification($contract));
        }

        // Dispatch new ContractStatusChangedNotification if status actually changed
        if ($finalStatus !== $originalStatus) {
            if ($contract->buyer) {
                $contract->buyer->notify(new ContractStatusChangedNotification($contract));
            }
            if ($contract->seller) {
                $contract->seller->notify(new ContractStatusChangedNotification($contract));
            }
        }

        $contract->load([
            'buyer.company',
            'seller.company',
            'quote.rfq',
            'items.product',
        ]);

        return $contract;
    }

    /**
     * Get success message for contract status
     */
    public function getStatusMessage(string $status): string
    {
        return match ($status) {
            Contract::STATUS_PENDING_APPROVAL             => 'Contract is pending approval',
            Contract::STATUS_APPROVED                     => 'Contract approved successfully',
            Contract::STATUS_PENDING_PAYMENT              => 'Contract approved and moved to pending payment',
            Contract::STATUS_PENDING_PAYMENT_CONFIRMATION => 'Payment confirmation is pending admin review',
            Contract::STATUS_IN_PROGRESS                  => 'Contract is now in progress',
            Contract::STATUS_VERIFY_SHIPMENT_URL          => 'Shipment URL is pending admin verification',
            Contract::STATUS_SHIPPED                      => 'Shipment URL verified successfully. Contract items have been shipped',
            Contract::STATUS_DELIVERED_AND_PAID           => 'Contract delivered and payment made to seller',
            Contract::STATUS_DELIVERED                    => 'Contract items have been delivered',
            Contract::STATUS_COMPLETED                    => 'Contract completed successfully',
            Contract::STATUS_CANCELLED                    => 'Contract has been cancelled',
            Contract::BUYER_PAYMENT_REJECTED              => 'Buyer payment has been rejected',
            default                                       => 'Contract updated successfully'
        };
    }

    /**
     * Handle bulk actions on contracts.
     */
    public function bulkActionContracts(array $contractIds, string $action, ?string $status = null): array
    {
        try {
            $validActions = ['delete', 'update_status'];

            if (! in_array($action, $validActions)) {
                return [
                    'success' => false,
                    'message' => 'Invalid bulk action',
                    'status'  => 400,
                ];
            }

            if ($action === 'update_status' && ! $status) {
                return [
                    'success' => false,
                    'message' => 'Status is required for update_status action',
                    'status'  => 400,
                ];
            }

            $contracts = Contract::whereIn('id', $contractIds)->get();

            if ($contracts->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No contracts found for the provided IDs',
                    'status'  => 404,
                ];
            }

            $successCount = 0;
            $errors = [];
            $updatedContracts = [];

            foreach ($contracts as $contract) {
                try {
                    switch ($action) {
                        case 'delete':
                            $contract->delete();
                            $successCount++;
                            break;
                        case 'update_status':
                            if ($status === Contract::STATUS_APPROVED && $contract->status === Contract::STATUS_PENDING_APPROVAL) {
                                $finalStatus = Contract::STATUS_PENDING_PAYMENT;
                            } else {
                                $finalStatus = $status;
                            }

                            if (! $contract->canTransitionTo($finalStatus)) {
                                $errors[] = "Contract {$contract->id}: Cannot transition from {$contract->status} to {$finalStatus}";
                            } else {
                                $updateData = ['status' => $finalStatus];

                                $contract->update($updateData);

                                $originalStatus = $contract->status; // Capture original status
                                $updateData = ['status' => $finalStatus]; // Define updateData for this scope
                                $contract->update($updateData);

                                $contract->load(['buyer', 'seller']); // Ensure buyer and seller are loaded

                                // Dispatch existing notifications
                                if ($finalStatus === Contract::STATUS_IN_PROGRESS) {
                                    $contract->seller->notify(new ContractInProgressNotification($contract));
                                } elseif ($finalStatus === Contract::STATUS_DELIVERED_AND_PAID) {
                                    $contract->seller->notify(new PaymentMadeToSellerNotification($contract));
                                }

                                // Dispatch new ContractStatusChangedNotification if status actually changed
                                if ($finalStatus !== $originalStatus) {
                                    if ($contract->buyer) {
                                        $contract->buyer->notify(new ContractStatusChangedNotification($contract));
                                    }
                                    if ($contract->seller) {
                                        $contract->seller->notify(new ContractStatusChangedNotification($contract));
                                    }
                                }

                                $contract->load([
                                    'buyer.company',
                                    'seller.company',
                                    'quote.rfq',
                                    'items.product',
                                ]);
                                $updatedContracts[] = $contract;
                                $successCount++;
                            }
                            break;
                    }
                } catch (Exception $e) {
                    $errors[] = "Contract {$contract->id}: ".$e->getMessage();
                }
            }

            $message = $successCount > 0
                ? "Bulk action completed successfully on {$successCount} contracts"
                : 'Bulk action failed';

            $responseData = [];

            if ($action === 'update_status' && ! empty($updatedContracts)) {
                $responseData['contracts'] = AdminContractResource::collection($updatedContracts);
            }

            return [
                'success' => $successCount > 0,
                'message' => $message,
                'data'    => $responseData,
                'status'  => $successCount > 0 ? 200 : 400,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to perform bulk action',
                'status'  => 500,
            ];
        }
    }

    /**
     * Apply custom filters for contract queries.
     */
    private function applyCustomFilters($query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['buyer_id'])) {
            $query->where('buyer_id', $filters['buyer_id']);
        }

        if (! empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (! empty($filters['quote_id'])) {
            $query->where('quote_id', $filters['quote_id']);
        }

        if (! empty($filters['contract_number'])) {
            $query->where('contract_number', 'like', '%'.$filters['contract_number'].'%');
        }

        if (! empty($filters['amount_min'])) {
            $query->where('total_amount', '>=', $filters['amount_min']);
        }

        if (! empty($filters['amount_max'])) {
            $query->where('total_amount', '<=', $filters['amount_max']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('contract_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('contract_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['delivery_from'])) {
            $query->whereDate('estimated_delivery', '>=', $filters['delivery_from']);
        }

        if (! empty($filters['delivery_to'])) {
            $query->whereDate('estimated_delivery', '<=', $filters['delivery_to']);
        }
    }
}
