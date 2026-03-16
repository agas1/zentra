<?php

namespace Database\Seeders;

use App\Domain\Plan\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'description' => 'Para experimentar o Zentra com projetos pessoais.',
                'max_members' => 2,
                'max_boards' => 5,
                'max_storage_mb' => 100,
                'max_labels' => 6,
                'max_attachment_size_mb' => 5,
                'features' => [],
                'price_monthly' => 0,
                'price_annual' => 0,
                'stripe_price_monthly_id' => null,
                'stripe_price_annual_id' => null,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Para times pequenos que querem organizar seus projetos.',
                'max_members' => 10,
                'max_boards' => -1,
                'max_storage_mb' => 2048,
                'max_labels' => -1,
                'max_attachment_size_mb' => 25,
                'features' => ['custom_backgrounds'],
                'price_monthly' => 29.00,
                'price_annual' => 278.40, // 29 * 12 * 0.8
                'stripe_price_monthly_id' => env('STRIPE_PRICE_STARTER_MONTHLY'),
                'stripe_price_annual_id' => env('STRIPE_PRICE_STARTER_ANNUAL'),
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'description' => 'Para times em crescimento que precisam de mais poder.',
                'max_members' => 30,
                'max_boards' => -1,
                'max_storage_mb' => 15360,
                'max_labels' => -1,
                'max_attachment_size_mb' => 100,
                'features' => ['custom_backgrounds', 'automations', 'custom_fields', 'priority_support', 'power_ups'],
                'price_monthly' => 79.00,
                'price_annual' => 758.40, // 79 * 12 * 0.8
                'stripe_price_monthly_id' => env('STRIPE_PRICE_PRO_MONTHLY'),
                'stripe_price_annual_id' => env('STRIPE_PRICE_PRO_ANNUAL'),
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'business'],
            [
                'name' => 'Business',
                'description' => 'Para empresas que precisam de controle total.',
                'max_members' => -1,
                'max_boards' => -1,
                'max_storage_mb' => 102400,
                'max_labels' => -1,
                'max_attachment_size_mb' => 250,
                'features' => ['custom_backgrounds', 'automations', 'custom_fields', 'priority_support', 'api_access', 'sso', 'power_ups'],
                'price_monthly' => 199.00,
                'price_annual' => 1910.40, // 199 * 12 * 0.8
                'stripe_price_monthly_id' => env('STRIPE_PRICE_BUSINESS_MONTHLY'),
                'stripe_price_annual_id' => env('STRIPE_PRICE_BUSINESS_ANNUAL'),
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 3,
            ]
        );
    }
}
