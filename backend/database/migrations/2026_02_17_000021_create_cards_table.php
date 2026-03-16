<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('list_id')->constrained('board_lists')->cascadeOnDelete();
            $table->foreignUuid('board_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->float('position')->default(0);
            $table->string('cover_url', 500)->nullable();
            $table->timestamp('due_date')->nullable();
            $table->boolean('due_completed')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->foreignUuid('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['list_id', 'is_archived', 'position']);
            $table->index(['board_id', 'is_archived']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
