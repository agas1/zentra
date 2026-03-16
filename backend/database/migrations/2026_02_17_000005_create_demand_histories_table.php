<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by_id')->constrained('users');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('demand_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_histories');
    }
};
