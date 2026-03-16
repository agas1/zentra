<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labels', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('board_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('color', 20);

            $table->index('board_id');
        });

        Schema::create('card_labels', function (Blueprint $table) {
            $table->foreignUuid('card_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('label_id')->constrained()->cascadeOnDelete();

            $table->primary(['card_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_labels');
        Schema::dropIfExists('labels');
    }
};
