<?php

namespace App\Providers;

use App\Contracts\Files\ImageProcessingInterface;
use App\Contracts\Stats\SeasonStrategy;
use App\Contracts\Stats\StatsRepository;
use App\Contracts\Teams\TeamMatchReadRepository;
use App\Events\ImageProcessingEvent;
use App\Listeners\ImageProcessingListener;
use App\Repositories\Stats\QueryBuilderStatsRepository;
use App\Repositories\Teams\QueryBuilderTeamMatchReadRepository;
use App\Services\Files\ImageProcessing;
use App\Services\Stats\AnnualSeasonStrategy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Typesense\Client;

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
        // Injection via interfaces:
        // - SeasonStrategy pointe vers la strategie de saison active (annuelle pour le moment).
        // - StatsRepository pointe vers l'implementation Query Builder pour centraliser l'acces stats.
        // - TeamMatchReadRepository : lectures match_events / match_results en Query Builder.
        $this->app->scoped(SeasonStrategy::class, AnnualSeasonStrategy::class);
        $this->app->scoped(StatsRepository::class, QueryBuilderStatsRepository::class);
        $this->app->scoped(TeamMatchReadRepository::class, QueryBuilderTeamMatchReadRepository::class);
        $this->app->scoped(ImageProcessingInterface::class, ImageProcessing::class);
        $this->app->scoped(Client::class, function () {
            $t = config('services.typesense');
            $client = new Client([
                'api_key' => $t['api_key'],
                'nodes' => [
                    [
                        'host' => $t['host'],
                        'port' => $t['port'],
                        'protocol' => $t['protocol'],
                    ],
                ],
                'connection_timeout_seconds' => 10,
            ]);

            return $client;
        });
    }

    public function boot(): void
    {
        Event::listen(ImageProcessingEvent::class, ImageProcessingListener::class);

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

        RateLimiter::for('auth-follow-write', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(20)->by($id.'|'.$request->ip());
        });

        RateLimiter::for('auth-follow-read', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(60)->by($id.'|'.$request->ip());
        });

        RateLimiter::for('auth-team-read', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(60)->by($id.'|'.$request->ip());
        });

        RateLimiter::for('auth-billing-read', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(60)->by($id.'|'.$request->ip());
        });

        RateLimiter::for('auth-billing-write', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(20)->by($id.'|'.$request->ip());
        });

        RateLimiter::for('auth-team-write', function (Request $request) {
            $id = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(30)->by($id.'|'.$request->ip());
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
