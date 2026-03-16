<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('power_ups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('category')->default('productivity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workspace_power_ups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('power_up_slug');
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->foreignUuid('connected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'power_up_slug']);
            $table->foreign('power_up_slug')->references('slug')->on('power_ups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_power_ups');
        Schema::dropIfExists('power_ups');
    }
};
