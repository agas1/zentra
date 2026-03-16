<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demands', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('assigned_designer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->constrained('users');
            $table->integer('remaining_revisions');
            $table->timestamps();

            $table->index('client_id');
            $table->index('status');
            $table->index('assigned_designer_id');
            $table->index('created_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demands');
    }
};
