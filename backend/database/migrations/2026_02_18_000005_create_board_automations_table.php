<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_automations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('trigger_type', 50);
            $table->json('trigger_config')->default('{}');
            $table->string('action_type', 50);
            $table->json('action_config')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['board_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_automations');
    }
};
