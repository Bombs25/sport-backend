<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Billing\BillingInvoiceListRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Laravel\Cashier\Invoice as CashierInvoice;

/**
 * Liste les factures Stripe du client connecté (tous statuts : payées, annulées, etc.).
 */
class BillingInvoiceListController extends Controller
{
    public function __invoke(BillingInvoiceListRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasStripeId()) {
            return response()->json(['invoices' => []]);
        }

        $validated = $request->validated();
        $limit = $validated['limit'] ?? 24;

        $parameters = [
            'limit' => $limit,
            'expand' => ['data.lines'],
        ];

        if (isset($validated['month'], $validated['year'])) {
            $tz = config('app.timezone', 'UTC');
            $start = Carbon::createFromDate((int) $validated['year'], (int) $validated['month'], 1, $tz)->startOfDay();
            $end = (clone $start)->endOfMonth()->endOfDay();
            $parameters['created'] = [
                'gte' => $start->timestamp,
                'lte' => $end->timestamp,
            ];
        }

        $invoices = $user->invoicesIncludingPending($parameters)
            ->map(fn (CashierInvoice $invoice): array => $this->serializeInvoice($invoice))
            ->values()
            ->all();

        return response()->json([
            'invoices' => $invoices,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInvoice(CashierInvoice $invoice): array
    {
        $stripe = $invoice->asStripeInvoice();

        $description = $stripe->description;
        if ($description === null || $description === '') {
            $lines = $stripe->lines?->data ?? [];
            $first = $lines[0] ?? null;
            $description = is_object($first) ? ($first->description ?? null) : null;
        }

        return [
            'id' => $stripe->id,
            'number' => $stripe->number,
            'created_at' => $invoice->date()->toIso8601String(),
            'description' => $description,
            'status' => $stripe->status,
            'currency' => strtoupper((string) $stripe->currency),
            'total_cents' => $invoice->rawRealTotal(),
            'total' => $invoice->realTotal(),
            'hosted_invoice_url' => $stripe->hosted_invoice_url,
            'invoice_pdf' => $stripe->invoice_pdf,
        ];
    }
}
