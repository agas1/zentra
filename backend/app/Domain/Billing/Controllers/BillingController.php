<?php

namespace App\Domain\Billing\Controllers;

use App\Domain\Billing\Services\StripeService;
use App\Domain\Plan\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Checkout\Session as CheckoutSession;

class BillingController
{
    public function __construct(private StripeService $stripe) {}

    public function createCheckout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'cycle' => 'sometimes|string|in:monthly,annual',
        ]);

        $workspace = $request->workspace;
        $plan = Plan::where('id', $data['plan_id'])->where('is_active', true)->firstOrFail();
        $cycle = $data['cycle'] ?? 'monthly';

        if ($plan->price_monthly <= 0) {
            return response()->json(['error' => ['message' => 'Nao e possivel fazer checkout de um plano gratuito.']], 422);
        }

        $priceId = $plan->getStripePriceId($cycle);
        if (!$priceId) {
            return response()->json(['error' => ['message' => 'Preco Stripe nao configurado para este plano.']], 422);
        }

        $session = $this->stripe->createCheckoutSession($workspace, $plan, $cycle);

        return response()->json([
            'data' => [
                'client_secret' => $session->client_secret,
                'session_id' => $session->id,
            ],
        ]);
    }

    public function checkoutStatus(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $session = CheckoutSession::retrieve($request->session_id);

        return response()->json([
            'data' => [
                'status' => $session->status,
                'payment_status' => $session->payment_status,
            ],
        ]);
    }

    public function changePlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'cycle' => 'sometimes|string|in:monthly,annual',
        ]);

        $workspace = $request->workspace;
        $plan = Plan::where('id', $data['plan_id'])->where('is_active', true)->firstOrFail();
        $cycle = $data['cycle'] ?? $workspace->billing_cycle;

        if (!$workspace->stripe_subscription_id) {
            return response()->json(['error' => ['message' => 'Nenhuma assinatura ativa encontrada.']], 422);
        }

        $this->stripe->changePlan($workspace, $plan, $cycle);

        $workspace->update([
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
        ]);

        return response()->json([
            'data' => $workspace->fresh()->load('plan'),
            'message' => 'Plano alterado com sucesso.',
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $workspace = $request->workspace;

        if (!$workspace->stripe_subscription_id) {
            return response()->json(['error' => ['message' => 'Nenhuma assinatura ativa encontrada.']], 422);
        }

        $subscription = $this->stripe->cancelSubscription($workspace);

        $workspace->update([
            'subscription_status' => 'canceled',
            'subscription_ends_at' => date('Y-m-d H:i:s', $subscription->current_period_end),
        ]);

        return response()->json([
            'data' => $workspace->fresh()->load('plan'),
            'message' => 'Assinatura cancelada. Acesso ate o fim do periodo.',
        ]);
    }

    public function resume(Request $request): JsonResponse
    {
        $workspace = $request->workspace;

        if (!$workspace->stripe_subscription_id) {
            return response()->json(['error' => ['message' => 'Nenhuma assinatura encontrada.']], 422);
        }

        $this->stripe->resumeSubscription($workspace);

        $workspace->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => null,
        ]);

        return response()->json([
            'data' => $workspace->fresh()->load('plan'),
            'message' => 'Assinatura reativada com sucesso.',
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $workspace = $request->workspace;
        $workspace->load('plan');

        return response()->json([
            'data' => [
                'plan' => $workspace->plan,
                'subscription_status' => $workspace->subscription_status,
                'billing_cycle' => $workspace->billing_cycle,
                'subscription_ends_at' => $workspace->subscription_ends_at,
                'has_active_subscription' => $workspace->hasActiveSubscription(),
                'is_free' => $workspace->isOnFreePlan(),
            ],
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $invoices = $this->stripe->getInvoices($request->workspace);

        return response()->json(['data' => $invoices]);
    }

    public function portalUrl(Request $request): JsonResponse
    {
        $workspace = $request->workspace;

        if (!$workspace->stripe_customer_id) {
            return response()->json(['error' => ['message' => 'Nenhum cliente Stripe vinculado.']], 422);
        }

        $url = $this->stripe->getBillingPortalUrl($workspace);

        return response()->json(['data' => ['url' => $url]]);
    }
}
