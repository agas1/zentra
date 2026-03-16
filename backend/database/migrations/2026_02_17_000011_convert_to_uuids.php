<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys and dependent tables first
        Schema::dropIfExists('demand_messages');
        Schema::dropIfExists('demand_histories');
        Schema::dropIfExists('demands');
        Schema::dropIfExists('users');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('plans');

        // Recreate plans with UUID
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->integer('monthly_credit_limit');
            $table->integer('revision_limit_per_demand');
            $table->integer('active_demand_limit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Recreate clients with UUID
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->foreignUuid('plan_id')->constrained('plans');
            $table->integer('cycle_start_day');
            $table->date('current_cycle_start');
            $table->date('current_cycle_end');
            $table->integer('remaining_credits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('is_active');
        });

        // Recreate users with UUID
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 20);
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('role');
        });

        // Recreate demands with UUID
        Schema::create('demands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->foreignUuid('client_id')->constrained('clients');
            $table->foreignUuid('assigned_designer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by_id')->constrained('users');
            $table->integer('remaining_revisions');
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });

        // Recreate demand_histories with UUID
        Schema::create('demand_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignUuid('changed_by_id')->constrained('users');
            $table->text('comment')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('demand_id');
        });

        // Recreate demand_messages with UUID
        Schema::create('demand_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        // Cannot undo UUID conversion cleanly
    }
};
