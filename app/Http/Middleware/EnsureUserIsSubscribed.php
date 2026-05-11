<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige un abonnement Cashier actif (`billing.subscription_type`) pour les actions protégées.
 */
class EnsureUserIsSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => __('Authentification requise.'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $type = (string) config('billing.subscription_type');

        if (! $user->subscribed($type)) {
            return response()->json([
                'message' => __('Un abonnement actif est requis pour cette action.'),
                'code' => 'subscription_required',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
