<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->string('event'); // created, upgraded, downgraded, canceled, payment_failed, payment_succeeded
            $table->foreignUuid('plan_id')->nullable()->constrained();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('brl');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_history');
    }
};
