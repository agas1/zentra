<?php

namespace App\Domain\Billing\Services;

use App\Domain\Plan\Models\Plan;
use App\Domain\Workspace\Models\Workspace;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Customer;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Invoice;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Webhook;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function getOrCreateCustomer(Workspace $workspace): Customer
    {
        if ($workspace->stripe_customer_id) {
            return Customer::retrieve($workspace->stripe_customer_id);
        }

        $owner = $workspace->owner;

        $customer = Customer::create([
            'email' => $owner->email,
            'name' => $owner->name,
            'metadata' => [
                'workspace_id' => $workspace->id,
                'workspace_name' => $workspace->name,
            ],
        ]);

        $workspace->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    public function createCheckoutSession(Workspace $workspace, Plan $plan, string $cycle = 'monthly'): CheckoutSession
    {
        $customer = $this->getOrCreateCustomer($workspace);
        $priceId = $plan->getStripePriceId($cycle);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        return CheckoutSession::create([
            'customer' => $customer->id,
            'mode' => 'subscription',
            'payment_method_types' => ['card', 'boleto'],
            'ui_mode' => 'embedded',
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'return_url' => $frontendUrl . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
            'metadata' => [
                'workspace_id' => $workspace->id,
                'plan_id' => $plan->id,
                'cycle' => $cycle,
            ],
            'subscription_data' => [
                'metadata' => [
                    'workspace_id' => $workspace->id,
                    'plan_id' => $plan->id,
                    'cycle' => $cycle,
                ],
            ],
        ]);
    }

    public function changePlan(Workspace $workspace, Plan $plan, string $cycle = 'monthly'): Subscription
    {
        $subscription = Subscription::retrieve($workspace->stripe_subscription_id);
        $priceId = $plan->getStripePriceId($cycle);

        return Subscription::update($subscription->id, [
            'items' => [
                [
                    'id' => $subscription->items->data[0]->id,
                    'price' => $priceId,
                ],
            ],
            'proration_behavior' => 'create_prorations',
            'metadata' => [
                'workspace_id' => $workspace->id,
                'plan_id' => $plan->id,
                'cycle' => $cycle,
            ],
        ]);
    }

    public function cancelSubscription(Workspace $workspace): Subscription
    {
        return Subscription::update($workspace->stripe_subscription_id, [
            'cancel_at_period_end' => true,
        ]);
    }

    public function resumeSubscription(Workspace $workspace): Subscription
    {
        return Subscription::update($workspace->stripe_subscription_id, [
            'cancel_at_period_end' => false,
        ]);
    }

    public function getBillingPortalUrl(Workspace $workspace): string
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        $session = PortalSession::create([
            'customer' => $workspace->stripe_customer_id,
            'return_url' => $frontendUrl . '/billing',
        ]);

        return $session->url;
    }

    public function getInvoices(Workspace $workspace, int $limit = 10): array
    {
        if (!$workspace->stripe_customer_id) {
            return [];
        }

        $invoices = Invoice::all([
            'customer' => $workspace->stripe_customer_id,
            'limit' => $limit,
        ]);

        return collect($invoices->data)->map(fn ($inv) => [
            'id' => $inv->id,
            'number' => $inv->number,
            'amount' => $inv->amount_paid / 100,
            'currency' => $inv->currency,
            'status' => $inv->status,
            'created_at' => date('Y-m-d H:i:s', $inv->created),
            'pdf_url' => $inv->invoice_pdf,
            'hosted_url' => $inv->hosted_invoice_url,
        ])->toArray();
    }

    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        return Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret')
        );
    }
}
