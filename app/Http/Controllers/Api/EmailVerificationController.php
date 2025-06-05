<?php

namespace App\Http\Controllers\Api;

use App\Services\EmailVerificationService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class EmailVerificationController extends BaseController
{
    use ApiResponse;

    public function __construct(private EmailVerificationService $service)
    {
        $this->middleware('auth:api')->except('verify');
        $this->middleware('throttle:6,1')->only(['send', 'resend']);
        $this->middleware(RestrictedDocsAccess::class);
    }

    /**
     * Send email verification
     *
     * @group Email Verification
     */
    public function send(): JsonResponse
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return $this->apiResponse(null, 'Email already verified', 400);
        }

        $this->service->sendVerification($user);

        return $this->apiResponse(null, 'Verification email sent');
    }

    /**
     * Resend email verification
     *
     * @group Email Verification
     */
    public function resend(): JsonResponse
    {
        return $this->send();
    }

    /**
     * Verify email address
     *
     * @group Email Verification
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string|size:64']);

        $verified = $this->service->verify($request->token);

        if (! $verified) {
            return $this->apiResponse(null, 'Invalid or expired token', 400);
        }

        return $this->apiResponse(null, 'Email verified successfully');
    }

    /**
     * Get email verification status
     *
     * @group Email Verification
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();

        return $this->apiResponse([
            'is_verified' => $user->hasVerifiedEmail(),
            'email' => $user->email,
        ]);
    }
}
