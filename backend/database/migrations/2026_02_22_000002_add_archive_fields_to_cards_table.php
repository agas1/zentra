<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->text('archive_reason')->nullable()->after('is_archived');
            $table->timestamp('unarchive_at')->nullable()->after('archive_reason');
            $table->foreignUuid('unarchive_list_id')
                ->nullable()
                ->after('unarchive_at')
                ->constrained('board_lists')
                ->nullOnDelete();

            $table->index(['is_archived', 'unarchive_at']);
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropIndex(['is_archived', 'unarchive_at']);
            $table->dropForeign(['unarchive_list_id']);
            $table->dropColumn(['archive_reason', 'unarchive_at', 'unarchive_list_id']);
        });
    }
};
