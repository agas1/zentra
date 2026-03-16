<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('stripe_price_monthly_id')->nullable()->after('price_monthly');
            $table->string('stripe_price_annual_id')->nullable()->after('stripe_price_monthly_id');
            $table->decimal('price_annual', 10, 2)->default(0)->after('stripe_price_annual_id');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['stripe_price_monthly_id', 'stripe_price_annual_id', 'price_annual']);
        });
    }
};
