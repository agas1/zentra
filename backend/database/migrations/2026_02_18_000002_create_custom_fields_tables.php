<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('board_id');
            $table->string('name', 100);
            $table->string('type', 30);
            $table->json('options')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index('board_id');
            $table->foreign('board_id')
                ->references('id')
                ->on('boards')
                ->cascadeOnDelete();
        });

        Schema::create('card_custom_field_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('card_id');
            $table->uuid('custom_field_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['card_id', 'custom_field_id']);
            $table->foreign('card_id')
                ->references('id')
                ->on('cards')
                ->cascadeOnDelete();
            $table->foreign('custom_field_id')
                ->references('id')
                ->on('custom_fields')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};
