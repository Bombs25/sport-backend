<?php

use App\Http\Controllers\Api\V1\Billing\BillingCancelSubscriptionController;
use App\Http\Controllers\Api\V1\Billing\BillingCheckoutController;
use App\Http\Controllers\Api\V1\Billing\BillingInvoiceListController;
use App\Http\Controllers\Api\V1\Billing\BillingPlanController;
use App\Http\Controllers\Api\V1\Billing\BillingSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
| Facturation Stripe (Laravel Cashier) — backend pour flux React Native.
| Checkout : `POST billing/checkout` ; factures : `GET billing/invoices` ; annulation : `POST billing/subscription/cancel` ; webhooks : `POST /stripe/webhook`.
| Forfait/prix affiché : `GET billing/plan` (public — lu avant connexion, ex. paywall onboarding).
*/
Route::prefix('v1')->group(function (): void {
    Route::get('billing/plan', BillingPlanController::class)->middleware('throttle:60,1');
});

Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    Route::get('billing/checkout', BillingCheckoutController::class)->middleware('throttle:auth-billing-write');
    Route::post('billing/subscription/cancel', BillingCancelSubscriptionController::class)->middleware('throttle:auth-billing-write');
    Route::get('billing/subscription', BillingSubscriptionController::class)->middleware('throttle:auth-billing-read');
    Route::get('billing/invoices', BillingInvoiceListController::class)->middleware('throttle:auth-billing-read');
});
