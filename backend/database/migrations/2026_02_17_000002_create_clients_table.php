<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('plan_id')->constrained('plans');
            $table->integer('cycle_start_day');
            $table->date('current_cycle_start');
            $table->date('current_cycle_end');
            $table->integer('remaining_credits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('plan_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
