<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_lists', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('board_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->float('position')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['board_id', 'is_archived', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_lists');
    }
};
