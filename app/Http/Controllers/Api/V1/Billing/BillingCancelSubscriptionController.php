<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Billing\BillingCancelSubscriptionRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Cashier\Subscription;
use Symfony\Component\HttpFoundation\Response;

/**
 * Annule l’abonnement Cashier du compte connecté (fin de période par défaut, ou immédiat si demandé).
 */
class BillingCancelSubscriptionController extends Controller
{
    public function __invoke(BillingCancelSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = (string) config('billing.subscription_type');
        /** @var Subscription|null $subscription */
        $subscription = $user->subscription($type);

        if ($subscription === null) {
            return response()->json([
                'message' => __('Aucun abonnement à annuler.'),
            ], Response::HTTP_NOT_FOUND);
        }

        if ($subscription->ended()) {
            return response()->json([
                'message' => __('Cet abonnement est déjà terminé.'),
            ], Response::HTTP_CONFLICT);
        }

        if ($subscription->canceled() && $subscription->onGracePeriod()) {
            return response()->json([
                'message' => __('L’annulation à la fin de la période de facturation est déjà programmée.'),
            ], Response::HTTP_CONFLICT);
        }

        $immediately = (bool) $request->boolean('immediately', false);

        if ($immediately) {
            $subscription->cancelNow();
        } else {
            $subscription->cancel();
        }

        $subscription->refresh();
        $subscribed = $user->subscribed($type);

        return response()->json([
            'message' => $immediately
                ? __('Abonnement annulé immédiatement.')
                : __('Abonnement annulé : accès jusqu’à la fin de la période en cours.'),
            'subscribed' => $subscribed,
            'subscription' => [
                'type' => $subscription->type,
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'created_at' => $subscription->created_at?->toIso8601String(),
            ],
            'has_incomplete_payment' => $user->hasIncompletePayment($type),
        ]);
    }
}
