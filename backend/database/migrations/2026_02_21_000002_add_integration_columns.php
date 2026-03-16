<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->string('google_calendar_event_id')->nullable()->after('is_archived');
        });

        Schema::table('card_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('card_attachments', 'source')) {
                $table->string('source')->default('local')->after('uploaded_by_id');
            }
            if (!Schema::hasColumn('card_attachments', 'external_url')) {
                $table->string('external_url')->nullable()->after('source');
            }
            if (!Schema::hasColumn('card_attachments', 'external_id')) {
                $table->string('external_id')->nullable()->after('external_url');
            }
            if (!Schema::hasColumn('card_attachments', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('google_calendar_event_id');
        });

        Schema::table('card_attachments', function (Blueprint $table) {
            $table->dropColumn(['source', 'external_url', 'external_id', 'created_at', 'updated_at']);
        });
    }
};
