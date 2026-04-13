<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\EmailChangeOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class EmailChangeOtpService
{
    private const OTP_TTL_SECONDS = 900;

    public function sendForUser(User $user, string $newEmail): void
    {
        $normalizedEmail = Str::lower($newEmail);
        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->cacheKey($user->id, $normalizedEmail), hash('sha256', $code), self::OTP_TTL_SECONDS);

        Notification::route('mail', $normalizedEmail)
            ->notify(new EmailChangeOtpNotification($code, $normalizedEmail));
    }

    public function verifyAndApply(User $user, string $newEmail, string $code): bool
    {
        $normalizedEmail = Str::lower($newEmail);
        $key = $this->cacheKey($user->id, $normalizedEmail);
        $stored = Cache::get($key);

        if ($stored === null || ! hash_equals($stored, hash('sha256', $code))) {
            return false;
        }

        Cache::forget($key);

        $alreadyTaken = DB::table('users')
            ->whereRaw('lower(email) = ?', [$normalizedEmail])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($alreadyTaken) {
            return false;
        }

        DB::table('users')->where('id', $user->id)->update([
            'email' => $normalizedEmail,
            'email_verified_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    private function cacheKey(int $userId, string $newEmail): string
    {
        return 'auth:email-change-otp:'.$userId.':'.hash('sha256', $newEmail);
    }
}
