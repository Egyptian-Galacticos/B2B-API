<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellerUpgradeRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellerUpgradeController extends Controller
{
    use ApiResponse;

    /**
     * Upgrade user to seller.
     *
     * This method allows a user to upgrade their account to a seller account.
     */
    public function upgradeToSeller(SellerUpgradeRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            assert($user instanceof User);

            if ($user->isSeller()) {
                return $this->apiResponse(null, 'User is already a seller', 400);
            }

            if (! $user->company) {
                return $this->apiResponse(null, 'User must have a company profile to upgrade to seller', 400);
            }

            DB::beginTransaction();

            try {
                $company = $user->company;

                $company->update([
                    'tax_id'                  => $request->tax_id,
                    'commercial_registration' => $request->commercial_registration,
                    'website'                 => $request->website,
                    'description'             => $request->description,
                ]);

                $user->assignRole('seller');

                DB::commit();

                return $this->apiResponse(
                    ['user' => new UserResource($user)],
                    'Successfully upgraded to seller',
                    201
                );
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Failed to upgrade to seller', 500);
        }
    }

    /**
     * Get the upgrade status of the user.
     *
     * This method checks if the user can upgrade to a seller account and returns the status.
     */
    public function getUpgradeStatus(): JsonResponse
    {
        try {
            $user = Auth::user();
            assert($user instanceof User);

            $status = [
                'can_upgrade'            => false,
                'is_seller'              => $user->isSeller(),
                'has_company'            => (bool) $user->company,
                'company_email_verified' => $user->company?->is_email_verified ?? false,
                'missing_seller_data'    => [],
            ];

            if ($user->company && ! $user->isSeller()) {
                $missing = [];

                if (! $user->company->tax_id) {
                    $missing[] = 'tax_id';
                }

                if (! $user->company->commercial_registration) {
                    $missing[] = 'commercial_registration';
                }

                $status['missing_seller_data'] = $missing;
                $status['can_upgrade'] = empty($missing);
            }

            if ($user->company) {
                $status['company'] = new CompanyResource($user->company);
            }

            return $this->apiResponse($status, 'Upgrade status retrieved successfully');
        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Failed to get upgrade status', 500);
        }
    }

    /**
     * Update company information.
     *
     * This method allows the authenticated user to update their company profile.
     */
    public function updateCompany(UpdateCompanyRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $company = $user->company;

            $updateData = array_filter([
                'name'                    => $request->name,
                'email'                   => $request->email,
                'tax_id'                  => $request->tax_id,
                'commercial_registration' => $request->commercial_registration,
                'company_phone'           => $request->company_phone,
                'address'                 => $request->address,
                'website'                 => $request->website,
                'description'             => $request->description,
                'logo'                    => $request->logo,
            ], fn ($value) => ! is_null($value));

            if (isset($updateData['email']) && $updateData['email'] !== $company->email) {
                $updateData['is_email_verified'] = false;
            }

            $company->update($updateData);

            return $this->apiResponse([
                'company' => new CompanyResource($company->refresh()),
            ], 'Company information updated successfully');
        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Failed to update company information', 500);
        }
    }

    /**
     * Get company information.
     *
     * This method retrieves the authenticated user's company profile.
     */
    public function getCompany(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->company) {
                return $this->apiResponse(null, 'User does not have a company profile', 404);
            }

            return $this->apiResponse([
                'company' => new CompanyResource($user->company),
            ], 'Company information retrieved successfully');
        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Failed to retrieve company information', 500);
        }
    }
}
