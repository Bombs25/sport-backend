<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forfait configuré (prix récurrent Stripe) pour l’affichage côté app — avant ou
 * après connexion (ex. paywall onboarding). Lecture **publique** : le prix n’est pas
 * sensible et doit s’afficher sans authentification. Mis en cache (le prix change rarement).
 */
class BillingPlanController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $priceId = config('billing.subscription_price_id');

        if (! is_string($priceId) || $priceId === '') {
            return response()->json([
                'message' => __('Configuration de facturation incomplète (prix Stripe).'),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $plan = Cache::remember(
                "billing.plan.{$priceId}",
                now()->addHours(6),
                fn (): array => $this->fetchPlan($priceId),
            );
        } catch (ApiErrorException) {
            return response()->json([
                'message' => __('Forfait indisponible pour le moment.'),
            ], Response::HTTP_BAD_GATEWAY);
        }

        $trialDays = (int) config('billing.trial_days', 0);

        return response()->json([
            ...$plan,
            'trial_days' => $trialDays > 0 ? $trialDays : null,
        ]);
    }

    /**
     * @return array{price_id: string, amount_cents: int|null, amount: string|null, currency: string, interval: string|null, interval_count: int, product_name: string|null}
     */
    private function fetchPlan(string $priceId): array
    {
        $price = Cashier::stripe()->prices->retrieve($priceId, ['expand' => ['product']]);

        $amountCents = $price->unit_amount;
        $product = $price->product;

        return [
            'price_id' => $price->id,
            'amount_cents' => $amountCents,
            'amount' => $amountCents !== null ? number_format($amountCents / 100, 2, '.', '') : null,
            'currency' => (string) $price->currency,
            'interval' => $price->recurring->interval ?? null,
            'interval_count' => (int) ($price->recurring->interval_count ?? 1),
            'product_name' => is_object($product) ? ($product->name ?? null) : null,
        ];
    }
}
