<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklists', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('card_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->float('position')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('checklist_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->boolean('is_checked')->default(false);
            $table->float('position')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklists');
    }
};
