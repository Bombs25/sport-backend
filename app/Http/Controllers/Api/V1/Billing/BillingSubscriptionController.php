<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;

/**
 * État d’abonnement du compte connecté (synchronisé via webhooks Cashier).
 */
class BillingSubscriptionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = (string) config('billing.subscription_type');
        $subscribed = $user->subscribed($type);
        /** @var Subscription|null $subscription */
        $subscription = $user->subscription($type);

        $payload = [
            'subscribed' => $subscribed,
            'subscription' => null,
            'has_incomplete_payment' => $user->hasIncompletePayment($type),
        ];

        if ($subscription !== null) {
            $payload['subscription'] = [
                'type' => $subscription->type,
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'created_at' => $subscription->created_at?->toIso8601String(),
            ];
        }

        return response()->json($payload);
    }
}
