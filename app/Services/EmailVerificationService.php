<?php

namespace App\Services;

use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Support\Str;

class EmailVerificationService
{
    public function sendVerification(User $user): void
    {
        EmailVerificationToken::where('user_id', $user->id)->delete();

        $plainToken = Str::random(64);
        $hashedToken = hash('sha256', $plainToken);

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token' => $hashedToken,
            'expires_at' => now()->addHour(),
        ]);

        $user->notify(new EmailVerificationNotification($plainToken));
    }

    public function verify(string $token): bool
    {
        $hashedToken = hash('sha256', $token);

        $verificationToken = EmailVerificationToken::with('user')
            ->where('token', $hashedToken)
            ->where('expires_at', '>', now())
            ->first();

        if (! $verificationToken || $verificationToken->user->hasVerifiedEmail()) {
            return false;
        }

        $verificationToken->user->update([
            'email_verified_at' => now(),
            'is_email_verified' => true,
        ]);

        $verificationToken->delete();

        return true;
    }
}
