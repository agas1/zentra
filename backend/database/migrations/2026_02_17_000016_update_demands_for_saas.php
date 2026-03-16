<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            // Add workspace_id referencing workspaces
            $table->foreignUuid('workspace_id')->nullable()->after('id')->constrained('workspaces');

            // Rename assigned_designer_id to assigned_to_id
            $table->renameColumn('assigned_designer_id', 'assigned_to_id');

            // Remove old columns
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'remaining_revisions']);
        });
    }

    public function down(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->foreignUuid('client_id')->nullable()->constrained('clients');
            $table->integer('remaining_revisions')->default(0);
            $table->renameColumn('assigned_to_id', 'assigned_designer_id');
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });
    }
};
