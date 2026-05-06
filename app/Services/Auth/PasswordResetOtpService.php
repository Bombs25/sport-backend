<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetOtpService
{
    private const OTP_TTL_SECONDS = 900;

    /**
     * Envoie un code à 6 chiffres si un compte existe pour cet e-mail. Sinon, noop (pas d’énumération de comptes).
     */
    public function sendResetCode(string $email): void
    {
        $email = Str::lower($email);

        $userId = DB::table('users')->whereRaw('lower(email) = ?', [$email])->value('id');

        if ($userId === null) {
            return;
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        Cache::store('app_main_cache')->put($this->cacheKey($email), [
            'user_id' => (int) $userId,
            'code_hash' => hash('sha256', $code),
        ], self::OTP_TTL_SECONDS);

        /** @var User $user */
        $user = User::query()->findOrFail($userId);
        $user->notify(new PasswordResetCodeNotification($code));
    }

    /**
     * Réinitialise le mot de passe si le code est valide. Révoque les tokens Sanctum existants.
     */
    public function resetPassword(string $email, string $code, string $password): bool
    {
        $email = Str::lower($email);
        $key = $this->cacheKey($email);
        $payload = Cache::store('app_main_cache')->get($key);

        if (! is_array($payload)
            || ! isset($payload['user_id'], $payload['code_hash'])
            || ! hash_equals($payload['code_hash'], hash('sha256', $code))) {
            return false;
        }

        Cache::store('app_main_cache')->forget($key);

        $user = User::query()->find($payload['user_id']);

        if ($user === null || Str::lower((string) $user->email) !== $email) {
            return false;
        }

        $user->forceFill(['password' => $password])->save();
        $user->tokens()->delete();

        return true;
    }

    private function cacheKey(string $normalizedEmail): string
    {
        return 'auth:password-reset-otp:'.hash('sha256', $normalizedEmail);
    }
}
