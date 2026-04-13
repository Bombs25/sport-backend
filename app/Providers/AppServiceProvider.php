<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Ce qu’il fait : enregistre les **rate limiters nommés** référencés par les routes API (ex. `throttle:auth-login`).
 *
 * Pourquoi : limiter brute force sur le login (par email + IP), le spam d’inscriptions, les demandes de
 * **mot de passe oublié** et les tentatives de reset OTP (par e-mail + IP), conformément aux bonnes pratiques
 * sécurité du guide O’Sport sans polluer `routes/api.php` de logique métier.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('register-credentials', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth-email-verify', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(6)->by($id.'|'.$request->ip());
        });

        RateLimiter::for('auth-email-resend', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(3)->by($id.'|'.$request->ip());
        });

        RateLimiter::for('auth-email-change-request', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(3)->by($id.'|'.$email.'|'.$request->ip());
        });

        RateLimiter::for('auth-email-change-verify', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(6)->by($id.'|'.$email.'|'.$request->ip());
        });

        RateLimiter::for('auth-password-change', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(3)->by($id.'|'.$request->ip());
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(3)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('auth-password-reset-otp', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(6)->by($email.'|'.$request->ip());
        });
    }
}
