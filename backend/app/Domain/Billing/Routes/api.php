<?php

use App\Domain\Billing\Controllers\BillingController;
use App\Domain\Billing\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Authenticated billing routes (JWT + workspace)
Route::middleware(['jwt', 'workspace'])->group(function () {
    Route::post('billing/checkout', [BillingController::class, 'createCheckout']);
    Route::get('billing/checkout/status', [BillingController::class, 'checkoutStatus']);
    Route::post('billing/change-plan', [BillingController::class, 'changePlan']);
    Route::post('billing/cancel', [BillingController::class, 'cancel']);
    Route::post('billing/resume', [BillingController::class, 'resume']);
    Route::get('billing/status', [BillingController::class, 'status']);
    Route::get('billing/invoices', [BillingController::class, 'invoices']);
    Route::get('billing/portal', [BillingController::class, 'portalUrl']);
});

// Stripe webhook (no auth — verified by Stripe signature)
Route::post('stripe/webhook', [WebhookController::class, 'handle']);
