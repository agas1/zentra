<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('card_id')->constrained()->cascadeOnDelete();
            $table->string('filename', 255);
            $table->string('path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->boolean('is_cover')->default(false);
            $table->foreignUuid('uploaded_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_attachments');
    }
};
