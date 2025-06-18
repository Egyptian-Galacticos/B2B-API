<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellerUpgradeRequest;
use App\Http\Resources\SellerUpgradeResource;
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

                $user->update(['status' => 'pending']);

                DB::commit();

                $user = $user->fresh()->load('company');

                return $this->apiResponse(
                    new SellerUpgradeResource($user),
                    'Successfully upgraded to seller. Your account is now pending admin approval.',
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
}
