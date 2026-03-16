<?php

namespace App\Domain\Billing\Commands;

use App\Domain\Plan\Models\Plan;
use Illuminate\Console\Command;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class SyncStripePlansCommand extends Command
{
    protected $signature = 'stripe:sync-plans';

    protected $description = 'Cria ou atualiza Products e Prices no Stripe para cada plano pago';

    public function handle(): int
    {
        $secret = config('services.stripe.secret');

        if (!$secret) {
            $this->error('STRIPE_SECRET_KEY nao configurada no .env');
            return 1;
        }

        Stripe::setApiKey($secret);

        $plans = Plan::where('price_monthly', '>', 0)->where('is_active', true)->orderBy('sort_order')->get();

        if ($plans->isEmpty()) {
            $this->warn('Nenhum plano pago encontrado.');
            return 0;
        }

        foreach ($plans as $plan) {
            $this->info("Processando plano: {$plan->name}...");

            // Create or find Stripe Product
            $product = Product::create([
                'name' => "Zentra {$plan->name}",
                'description' => $plan->description,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'plan_slug' => $plan->slug,
                ],
            ]);

            $this->line("  Product criado: {$product->id}");

            // Create monthly price
            $monthlyPrice = Price::create([
                'product' => $product->id,
                'unit_amount' => (int) ($plan->price_monthly * 100),
                'currency' => 'brl',
                'recurring' => ['interval' => 'month'],
                'metadata' => [
                    'plan_id' => $plan->id,
                    'cycle' => 'monthly',
                ],
            ]);

            $this->line("  Price mensal: {$monthlyPrice->id} (R$ {$plan->price_monthly}/mes)");

            // Create annual price
            $annualAmount = $plan->price_annual > 0
                ? (int) ($plan->price_annual * 100)
                : (int) ($plan->price_monthly * 12 * 0.8 * 100);

            $annualPrice = Price::create([
                'product' => $product->id,
                'unit_amount' => $annualAmount,
                'currency' => 'brl',
                'recurring' => ['interval' => 'year'],
                'metadata' => [
                    'plan_id' => $plan->id,
                    'cycle' => 'annual',
                ],
            ]);

            $annualDisplay = number_format($annualAmount / 100, 2, ',', '.');
            $this->line("  Price anual: {$annualPrice->id} (R$ {$annualDisplay}/ano)");

            // Update plan in database
            $plan->update([
                'stripe_price_monthly_id' => $monthlyPrice->id,
                'stripe_price_annual_id' => $annualPrice->id,
                'price_annual' => $annualAmount / 100,
            ]);

            $this->info("  Plano {$plan->name} atualizado com sucesso!");
        }

        $this->newLine();
        $this->info('Todos os planos foram sincronizados com o Stripe!');

        return 0;
    }
}
