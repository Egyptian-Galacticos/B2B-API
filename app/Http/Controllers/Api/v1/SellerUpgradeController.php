<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellerUpgradeRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellerUpgradeController extends Controller
{
    use ApiResponse;

    /**
     * Upgrade user to seller by updating company profile with required seller data
     */
    public function upgradeToSeller(SellerUpgradeRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            assert($user instanceof User);
            // Check if user is already a seller
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

                $company->refresh();

                return $this->apiResponse([
                    'user' => [
                        'id'        => $user->id,
                        'full_name' => $user->full_name,
                        'email'     => $user->email,
                        'roles'     => $user->getRoleNames(),
                    ],
                    'company' => [
                        'id'                      => $company->id,
                        'name'                    => $company->name,
                        'email'                   => $company->email,
                        'tax_id'                  => $company->tax_id,
                        'commercial_registration' => $company->commercial_registration,
                        'company_phone'           => $company->company_phone,
                        'address'                 => $company->address,
                        'website'                 => $company->website,
                        'description'             => $company->description,
                        'logo'                    => $company->logo,
                        'is_email_verified'       => $company->is_email_verified,
                    ],
                ], 'Successfully upgraded to seller', 201);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return $this->apiResponse(
                null,
                'Failed to upgrade to seller',
                500
            );
        }
    }

    public function getUpgradeStatus(): JsonResponse
    {
        try {
            $user = Auth::user();
            assert($user instanceof User);

            $status = [
                'can_upgrade'            => false,
                'is_seller'              => $user->isSeller(),
                'has_company'            => (bool) $user->company,
                'company_email_verified' => $user->company ? $user->company->is_email_verified : false,
                'missing_seller_data'    => [],
            ];

            if ($user->company && ! $user->isSeller()) {
                $missingData = [];

                if (! $user->company->tax_id) {
                    $missingData[] = 'tax_id';
                }

                if (! $user->company->commercial_registration) {
                    $missingData[] = 'commercial_registration';
                }

                $status['missing_seller_data'] = $missingData;
                $status['can_upgrade'] = empty($missingData);
            }

            if ($user->company) {
                $status['company'] = [
                    'id'                      => $user->company->id,
                    'name'                    => $user->company->name,
                    'email'                   => $user->company->email,
                    'tax_id'                  => $user->company->tax_id,
                    'commercial_registration' => $user->company->commercial_registration,
                    'company_phone'           => $user->company->company_phone,
                    'address'                 => $user->company->address,
                    'website'                 => $user->company->website,
                    'description'             => $user->company->description,
                    'logo'                    => $user->company->logo,
                    'is_email_verified'       => $user->company->is_email_verified,
                ];
            }

            return $this->apiResponse($status, 'Upgrade status retrieved successfully');

        } catch (\Exception $e) {
            return $this->apiResponse(
                null,
                'Failed to get upgrade status',
                500
            );
        }
    }

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
            ], function ($value) {
                return $value !== null;
            });

            if (isset($updateData['email']) && $updateData['email'] !== $company->email) {
                $updateData['is_email_verified'] = false;
            }

            $company->update($updateData);
            $company->refresh();

            return $this->apiResponse([
                'company' => [
                    'id'                      => $company->id,
                    'name'                    => $company->name,
                    'email'                   => $company->email,
                    'tax_id'                  => $company->tax_id,
                    'commercial_registration' => $company->commercial_registration,
                    'company_phone'           => $company->company_phone,
                    'address'                 => $company->address,
                    'website'                 => $company->website,
                    'description'             => $company->description,
                    'logo'                    => $company->logo,
                    'is_email_verified'       => $company->is_email_verified,
                ],
            ], 'Company information updated successfully');

        } catch (\Exception $e) {
            return $this->apiResponse(
                null,
                'Failed to update company information',
                500
            );
        }
    }

    public function getCompany(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->company) {
                return $this->apiResponse(null, 'User does not have a company profile', 404);
            }

            $company = $user->company;

            return $this->apiResponse([
                'company' => [
                    'id'                      => $company->id,
                    'name'                    => $company->name,
                    'email'                   => $company->email,
                    'tax_id'                  => $company->tax_id,
                    'commercial_registration' => $company->commercial_registration,
                    'company_phone'           => $company->company_phone,
                    'address'                 => $company->address,
                    'website'                 => $company->website,
                    'description'             => $company->description,
                    'logo'                    => $company->logo,
                    'is_email_verified'       => $company->is_email_verified,
                    'created_at'              => $company->created_at,
                    'updated_at'              => $company->updated_at,
                ],
            ], 'Company information retrieved successfully');

        } catch (\Exception $e) {
            return $this->apiResponse(
                null,
                'Failed to retrieve company information',
                500
            );
        }
    }
}
