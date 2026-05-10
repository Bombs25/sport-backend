<?php

use App\Http\Controllers\Api\V1\Billing\BillingCancelSubscriptionController;
use App\Http\Controllers\Api\V1\Billing\BillingCheckoutController;
use App\Http\Controllers\Api\V1\Billing\BillingInvoiceListController;
use App\Http\Controllers\Api\V1\Billing\BillingSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
| Facturation Stripe (Laravel Cashier) — backend pour flux React Native.
| Checkout : `POST billing/checkout` ; factures : `GET billing/invoices` ; annulation : `POST billing/subscription/cancel` ; webhooks : `POST /stripe/webhook`.
*/
Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    Route::post('billing/checkout', BillingCheckoutController::class)->middleware('throttle:auth-billing-write');
    Route::post('billing/subscription/cancel', BillingCancelSubscriptionController::class)->middleware('throttle:auth-billing-write');
    Route::get('billing/subscription', BillingSubscriptionController::class)->middleware('throttle:auth-billing-read');
    Route::get('billing/invoices', BillingInvoiceListController::class)->middleware('throttle:auth-billing-read');
});
