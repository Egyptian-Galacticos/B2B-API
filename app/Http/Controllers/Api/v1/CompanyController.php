<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Services\EmailVerificationService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    use ApiResponse;

    /**
     * Update company information
     *
     * @authenticated
     */
    public function update(UpdateProfileCompanyRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->company) {
                return $this->apiResponseErrors(
                    'Company not found',
                    ['No company found for this user.'],
                    404
                );
            }

            $validated = $request->validated();
            $updateData = collect($validated)->except(['logo'])->toArray();
            $user->company->update($updateData);

            if ($user->company->wasChanged('email')) {
                $user->company->setUnverifiedEmail();
                app(EmailVerificationService::class)->sendCompanyVerification($user->company);
            }

            if ($request->remove_logo) {
                $user->company->clearMediaCollection('logo');
            }
            if ($request->hasFile('logo')) {
                $user->company->clearMediaCollection('logo');
                $user->company
                    ->addMedia($request->file('logo'))
                    ->usingName('Company Logo - '.$request->file('logo')->getClientOriginalName())
                    ->toMediaCollection('logo');
            }

            $companyData = new CompanyResource($user->company->fresh()->load('media'));

            return $this->apiResponse($companyData, 'Company information updated successfully.', 200);
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Server error',
                ['An unexpected error occurred while updating the company information.', $e->getMessage()],
                500
            );
        }
    }
}
