<?php

use App\Http\Middleware\EnsureUserIsSubscribed;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.subscribed' => EnsureUserIsSubscribed::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->statefulApi();
        /*
         * Pas de route `login` nommée dans ce projet : le défaut Laravel `route('login')` casse l’auth API
         * (clients type RapidAPI sans `Accept: application/json`). Les invités API ne sont pas redirigés ;
         * `shouldRenderJsonWhen` ci‑dessous renvoie alors du JSON 401 au lieu du HTML `/`.
         */
        $middleware->redirectGuestsTo(function (Request $request): ?string {
            return $request->is('api/*') ? null : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
