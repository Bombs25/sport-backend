<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailOtpNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Cache;

class EmailVerificationOtpService
{
    private const OTP_TTL_SECONDS = 900;

    public function sendForUser(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->cacheKey($user->id), hash('sha256', $code), self::OTP_TTL_SECONDS);

        $user->notify(new VerifyEmailOtpNotification($code));
    }

    public function verify(User $user, string $code): bool
    {
        $key = $this->cacheKey($user->id);
        $stored = Cache::get($key);

        if ($stored === null || ! hash_equals($stored, hash('sha256', $code))) {
            return false;
        }

        Cache::forget($key);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return true;
    }

    private function cacheKey(int $userId): string
    {
        return 'auth:email-verify-otp:'.$userId;
    }
}
