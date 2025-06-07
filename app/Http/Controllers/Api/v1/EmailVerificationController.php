<?php

namespace App\Http\Controllers\Api\v1;

use App\Services\EmailVerificationService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class EmailVerificationController extends BaseController
{
    use ApiResponse;

    public function __construct(private EmailVerificationService $service)
    {
        $this->middleware('throttle:6,1')->only(['send', 'resend', 'sendCompany', 'resendCompany']);
        $this->middleware(RestrictedDocsAccess::class);
    }

    private function handelMail(bool $isResend = false): JsonResponse
    {
        try {
            $user = auth()->user();

            if ($user->hasVerifiedEmail()) {
                return $this->apiResponse(null, 'Email already verified', 400);
            }

            $this->service->sendVerification($user);

            $message = $isResend ? 'Verification email resent' : 'Verification email sent';

            return $this->apiResponse(null, $message);
        } catch (Exception $e) {
            return $this->apiResponse(null, 'Failed to send verification email', 500);
        }
    }

    public function send(): JsonResponse
    {
        return $this->handelMail(false);
    }

    public function resend(): JsonResponse
    {
        return $this->handelMail(true);
    }

    private function handleCompanySend(bool $isResend = false): JsonResponse
    {
        try {
            $user = auth()->user();
            $company = $user->company;

            if (! $company) {
                return $this->apiResponse(null, 'No company associated with this user', 400);
            }

            if ($company->hasVerifiedEmail()) {
                return $this->apiResponse(null, 'Company email already verified', 400);
            }

            $this->service->sendCompanyVerification($company);

            $message = $isResend ? 'Company verification email resent' : 'Company verification email sent';

            return $this->apiResponse(null, $message);
        } catch (Exception $e) {
            return $this->apiResponse(null, 'Failed to send company verification email', 500);
        }
    }

    public function sendCompany(): JsonResponse
    {
        return $this->handleCompanySend(false);
    }

    public function resendCompany(): JsonResponse
    {
        return $this->handleCompanySend(true);
    }

    public function verify(Request $request): JsonResponse
    {
        try {
            $request->validate(['token' => 'required|string|size:64']);

            $verified = $this->service->verify($request->token);

            if (! $verified) {
                return $this->apiResponse(null, 'Invalid or expired token', 400);
            }

            return $this->apiResponse(null, 'Email verified successfully');
        } catch (Exception $e) {
            return $this->apiResponse(null, 'Email verification failed', 500);
        }
    }

    public function status(): JsonResponse
    {
        try {
            $user = auth()->user();
            $status = $this->service->getVerificationStatus($user);

            return $this->apiResponse($status);
        } catch (Exception $e) {
            return $this->apiResponse(null, 'Failed to retrieve status', 500);
        }
    }
}
