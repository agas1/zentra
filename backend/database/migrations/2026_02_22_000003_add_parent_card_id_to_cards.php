<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->foreignUuid('parent_card_id')
                ->nullable()
                ->after('created_by_id')
                ->constrained('cards')
                ->cascadeOnDelete();

            $table->index('parent_card_id');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropForeign(['parent_card_id']);
            $table->dropIndex(['parent_card_id']);
            $table->dropColumn('parent_card_id');
        });
    }
};
