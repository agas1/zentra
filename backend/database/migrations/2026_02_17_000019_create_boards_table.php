<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('background_color', 20)->default('#0079bf');
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->foreignUuid('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'is_archived']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
