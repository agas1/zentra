<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cards - due_date index (board_id+is_archived and list_id+is_archived already exist)
        Schema::table('cards', function (Blueprint $table) {
            $table->index('due_date');
        });

        // Card comments - no indexes exist
        Schema::table('card_comments', function (Blueprint $table) {
            $table->index('card_id');
            $table->index('user_id');
        });

        // Card attachments - no indexes exist
        Schema::table('card_attachments', function (Blueprint $table) {
            $table->index('card_id');
        });

        // Card activities - card_id+created_at already exists, add user_id
        Schema::table('card_activities', function (Blueprint $table) {
            $table->index('user_id');
        });

        // Checklists and items - no indexes exist
        Schema::table('checklists', function (Blueprint $table) {
            $table->index('card_id');
        });

        Schema::table('checklist_items', function (Blueprint $table) {
            $table->index('checklist_id');
        });

        // Chat - conversation_participants: unique on (conversation_id, user_id) exists, add user_id alone
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->index('user_id');
        });

        // Messages - conversation_id+created_at already exists, add user_id
        Schema::table('messages', function (Blueprint $table) {
            $table->index('user_id');
        });

        // Workspace members - unique on (workspace_id, user_id) exists, add user_id alone for reverse lookups
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->index('user_id');
        });

        // Notifications - queried by user, sorted by created_at
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['user_id', 'read_at', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
        });

        Schema::table('card_comments', function (Blueprint $table) {
            $table->dropIndex(['card_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('card_attachments', function (Blueprint $table) {
            $table->dropIndex(['card_id']);
        });

        Schema::table('card_activities', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('checklists', function (Blueprint $table) {
            $table->dropIndex(['card_id']);
        });

        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropIndex(['checklist_id']);
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('workspace_members', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'read_at', 'created_at']);
            });
        }
    }
};
