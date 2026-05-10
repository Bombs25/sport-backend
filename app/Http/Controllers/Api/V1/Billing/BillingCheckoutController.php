<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Crée une session Stripe Checkout (mode subscription) pour l’app React Native.
 *
 * Réponse : {@code checkout_url} à ouvrir dans un navigateur / WebView ; les webhooks
 * synchronisent l’abonnement (voir {@see WebhookController}).
 */
class BillingCheckoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = (string) config('billing.subscription_type');
        $priceId = config('billing.subscription_price_id');
        $successUrl = config('billing.checkout_success_url');
        $cancelUrl = config('billing.checkout_cancel_url');

        if (! is_string($priceId) || $priceId === '' || ! is_string($successUrl) || $successUrl === '' || ! is_string($cancelUrl) || $cancelUrl === '') {
            return response()->json([
                'message' => __('Configuration de facturation incomplète (prix Stripe et URLs de retour).'),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if ($user->subscribed($type)) {
            return response()->json([
                'message' => __('Un abonnement actif existe déjà.'),
            ], Response::HTTP_CONFLICT);
        }

        $builder = $user->newSubscription($type, $priceId);
        $trialDays = (int) config('billing.trial_days', 0);
        if ($trialDays > 0) {
            $builder->trialDays($trialDays);
        }

        $checkout = $builder->checkout([
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        return response()->json(array_filter([
            'checkout_url' => $checkout->url,
            'session_id' => $checkout->id,
            'trial_days' => $trialDays > 0 ? $trialDays : null,
        ]));
    }
}
