<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Notifications\CompanyEmailVerificationNotification;
use App\Notifications\EmailVerificationNotification;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EmailVerificationService
{
    public function sendVerification(User $user): void
    {
        $user->emailVerificationTokens()->delete();

        $token = $this->createToken($user);

        $user->notify(new EmailVerificationNotification($token->token));
    }

    public function sendCompanyVerification(Company $company): void
    {
        $company->emailVerificationTokens()->delete();

        $token = $this->createToken($company);

        $company->notify(new CompanyEmailVerificationNotification($token->token));
    }

    private function createToken($verifiable): EmailVerificationToken
    {
        return EmailVerificationToken::create([
            'token'           => Str::random(64),
            'email'           => $verifiable->email,
            'expires_at'      => Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
            'verifiable_type' => get_class($verifiable),
            'verifiable_id'   => $verifiable->id,
        ]);
    }

    public function verify(string $token): bool
    {
        $verificationToken = EmailVerificationToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $verificationToken) {
            return false;
        }

        $verifiable = $verificationToken->verifiable;

        if ($verifiable instanceof User) {
            $verifiable->update([
                'is_email_verified' => true,
                'email_verified_at' => now(),
            ]);
        } elseif ($verifiable instanceof Company) {
            $verifiable->update([
                'is_email_verified' => true,
            ]);
        }

        $verificationToken->delete();

        return true;
    }

    public function getVerificationStatus(User $user): array
    {
        $company = $user->company;

        return [
            'user' => [
                'is_verified' => $user->hasVerifiedEmail(),
                'email'       => $user->email,
            ],
            'company' => $company ? [
                'is_verified' => $company->hasVerifiedEmail(),
                'email'       => $company->email,
            ] : null,
        ];
    }
}
