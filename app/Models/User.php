<?php

namespace App\Models;

use App\Services\Auth\EmailVerificationOtpService;
use App\Services\Notifications\ExpoPushService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        /** Jeton Expo push (`ExponentPushToken[...]`) — rempli par l'app, utilisé par {@see ExpoPushService}. */
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Envoie un **code à 6 chiffres** par e-mail (aligné écran RN « Vérification »), pas le lien signé Laravel.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(EmailVerificationOtpService::class)->sendForUser($this);
    }

    /**
     * Jeton push Expo pour les canaux Laravel / jobs ({@see ExpoPushService}).
     *
     * Retourne null si absent, format invalide, FCM natif en base, ou jeton de démo en local.
     */
    public function routeNotificationForFcm(?string $logContext = null): ?string
    {
        $raw = $this->fcm_token;
        $rejectReason = null;
        $resolved = null;

        if (! is_string($raw) || $raw === '') {
            $rejectReason = 'empty';
        } elseif (self::isNativeFcmToken($raw)) {
            $rejectReason = 'native_fcm';
        } elseif (! self::isExpoPushTokenFormat($raw)) {
            $rejectReason = 'invalid_expo_format';
        } elseif (self::shouldSkipExpoTokenInCurrentEnvironment($raw)) {
            $rejectReason = 'demo_skipped_local';
            $this->clearExpoPushToken();
        } else {
            $resolved = $raw;
        }

        if ($logContext !== null) {
            self::logFcmTokenResolution($this->id, $raw, $resolved, $rejectReason, $logContext);
        }

        return $resolved;
    }

    public function clearExpoPushToken(): void
    {
        if ($this->fcm_token === null) {
            return;
        }

        $this->forceFill(['fcm_token' => null])->save();
    }

    public static function clearExpoPushTokenValue(string $token): int
    {
        return (int) self::query()
            ->where('fcm_token', $token)
            ->update(['fcm_token' => null]);
    }

    public static function isExpoPushTokenFormat(string $token): bool
    {
        return str_starts_with($token, 'ExponentPushToken[') && str_ends_with($token, ']');
    }

    public static function isNativeFcmToken(string $token): bool
    {
        return str_contains($token, ':APA91') || str_starts_with($token, 'APA91');
    }

    public static function isDemoExpoPushToken(string $token): bool
    {
        if (! self::isExpoPushTokenFormat($token)) {
            return false;
        }

        $inner = Str::between($token, 'ExponentPushToken[', ']');

        return $inner !== '' && stripos($inner, 'demo') !== false;
    }

    public static function shouldSkipExpoTokenInCurrentEnvironment(string $token): bool
    {
        return app()->environment('local', 'testing') && self::isDemoExpoPushToken($token);
    }

    /**
     * @param  iterable<int, User>|Collection<int, User>  $users
     * @return list<string>
     */
    public static function expoPushTokensFrom(iterable $users, ?string $logContext = null): array
    {
        $tokens = [];
        $perUser = [];

        foreach ($users as $user) {
            $raw = $user->fcm_token;
            $resolved = $user->routeNotificationForFcm($logContext);

            $perUser[] = [
                'user_id' => $user->id,
                'fcm_token_db' => self::formatTokenForLog($raw),
                'route_notification_for_fcm' => self::formatTokenForLog($resolved),
                'used_for_push' => is_string($resolved) && $resolved !== '',
            ];

            if (is_string($resolved) && $resolved !== '') {
                $tokens[] = $resolved;
            }
        }

        $unique = array_values(array_unique($tokens));

        if ($logContext !== null) {
            logger()->debug('FCM tokens collectés pour push Expo', [
                'context' => $logContext,
                'recipient_count' => count($perUser),
                'expo_token_count' => count($unique),
                'users' => $perUser,
                'expo_tokens' => array_map(self::formatTokenForLog(...), $unique),
            ]);
        }

        return $unique;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function logFcmTokenResolution(
        int $userId,
        mixed $raw,
        ?string $resolved,
        ?string $rejectReason,
        string $context,
        array $extra = [],
    ): void {
        logger()->debug('FCM token résolution (User::routeNotificationForFcm)', array_merge([
            'context' => $context,
            'user_id' => $userId,
            'fcm_token_db' => self::formatTokenForLog(is_string($raw) ? $raw : null),
            'route_notification_for_fcm' => self::formatTokenForLog($resolved),
            'reject_reason' => $rejectReason,
        ], $extra));
    }

    public static function formatTokenForLog(?string $token): ?string
    {
        if ($token === null || $token === '') {
            return null;
        }

        if (app()->environment('local')) {
            return $token;
        }

        if (strlen($token) <= 28) {
            return substr($token, 0, 12).'…';
        }

        return substr($token, 0, 22).'…'.substr($token, -8);
    }
}
