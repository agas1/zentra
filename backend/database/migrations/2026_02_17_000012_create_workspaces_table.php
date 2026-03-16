<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->foreignUuid('plan_id')->constrained('plans');
            $table->foreignUuid('owner_id')->constrained('users');
            $table->integer('cycle_start_day')->default(1);
            $table->date('current_cycle_start');
            $table->date('current_cycle_end');
            $table->integer('remaining_credits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
