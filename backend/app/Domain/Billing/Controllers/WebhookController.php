<?php

namespace App\Domain\Billing\Controllers;

use App\Domain\Billing\Models\SubscriptionHistory;
use App\Domain\Billing\Services\StripeService;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Plan\Models\Plan;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController
{
    public function __construct(
        private StripeService $stripe,
        private NotificationService $notifications,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = $this->stripe->constructWebhookEvent($payload, $signature);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'invoice.paid' => $this->handleInvoicePaid($event->data->object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event->data->object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $workspaceId = $session->metadata->workspace_id ?? null;
        $planId = $session->metadata->plan_id ?? null;
        $cycle = $session->metadata->cycle ?? 'monthly';

        if (!$workspaceId || !$planId) {
            return;
        }

        $workspace = Workspace::find($workspaceId);
        if (!$workspace) {
            return;
        }

        $workspace->update([
            'plan_id' => $planId,
            'stripe_subscription_id' => $session->subscription,
            'subscription_status' => 'active',
            'billing_cycle' => $cycle,
            'subscription_ends_at' => null,
        ]);

        SubscriptionHistory::create([
            'workspace_id' => $workspaceId,
            'stripe_subscription_id' => $session->subscription,
            'event' => 'created',
            'plan_id' => $planId,
            'amount' => ($session->amount_total ?? 0) / 100,
            'currency' => $session->currency ?? 'brl',
            'metadata' => ['session_id' => $session->id, 'cycle' => $cycle],
        ]);
    }

    private function handleInvoicePaid(object $invoice): void
    {
        $subscriptionId = $invoice->subscription;
        $workspace = Workspace::where('stripe_subscription_id', $subscriptionId)->first();

        if (!$workspace) {
            return;
        }

        $workspace->update(['subscription_status' => 'active']);

        SubscriptionHistory::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => $subscriptionId,
            'stripe_invoice_id' => $invoice->id,
            'event' => 'payment_succeeded',
            'plan_id' => $workspace->plan_id,
            'amount' => ($invoice->amount_paid ?? 0) / 100,
            'currency' => $invoice->currency ?? 'brl',
        ]);
    }

    private function handleInvoicePaymentFailed(object $invoice): void
    {
        $subscriptionId = $invoice->subscription;
        $workspace = Workspace::where('stripe_subscription_id', $subscriptionId)->first();

        if (!$workspace) {
            return;
        }

        $workspace->update(['subscription_status' => 'past_due']);

        SubscriptionHistory::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => $subscriptionId,
            'stripe_invoice_id' => $invoice->id,
            'event' => 'payment_failed',
            'plan_id' => $workspace->plan_id,
            'amount' => ($invoice->amount_due ?? 0) / 100,
            'currency' => $invoice->currency ?? 'brl',
        ]);

        // Notify workspace owner
        $this->notifications->notifyUser(
            $workspace->owner_id,
            'payment_failed',
            'Pagamento falhou',
            'O pagamento da sua assinatura falhou. Atualize seu metodo de pagamento para manter o acesso.',
            ['workspace_id' => $workspace->id]
        );
    }

    private function handleSubscriptionUpdated(object $subscription): void
    {
        $workspace = Workspace::where('stripe_subscription_id', $subscription->id)->first();

        if (!$workspace) {
            return;
        }

        $planId = $subscription->metadata->plan_id ?? null;
        $cycle = $subscription->metadata->cycle ?? $workspace->billing_cycle;

        $updates = [
            'subscription_status' => $this->mapStripeStatus($subscription->status),
            'billing_cycle' => $cycle,
        ];

        if ($planId) {
            $updates['plan_id'] = $planId;
        }

        if ($subscription->cancel_at_period_end) {
            $updates['subscription_ends_at'] = date('Y-m-d H:i:s', $subscription->current_period_end);
            $updates['subscription_status'] = 'canceled';
        } else {
            $updates['subscription_ends_at'] = null;
        }

        $workspace->update($updates);
    }

    private function handleSubscriptionDeleted(object $subscription): void
    {
        $workspace = Workspace::where('stripe_subscription_id', $subscription->id)->first();

        if (!$workspace) {
            return;
        }

        $freePlan = Plan::where('slug', 'free')->first();

        $workspace->update([
            'plan_id' => $freePlan?->id ?? $workspace->plan_id,
            'stripe_subscription_id' => null,
            'subscription_status' => 'none',
            'billing_cycle' => 'monthly',
            'subscription_ends_at' => null,
        ]);

        SubscriptionHistory::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => $subscription->id,
            'event' => 'canceled',
            'plan_id' => $freePlan?->id,
            'amount' => 0,
        ]);
    }

    private function mapStripeStatus(string $status): string
    {
        return match ($status) {
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'canceled',
            'trialing' => 'trialing',
            'unpaid' => 'past_due',
            default => 'none',
        };
    }
}
