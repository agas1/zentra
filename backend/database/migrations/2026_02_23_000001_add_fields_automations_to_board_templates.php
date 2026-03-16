<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_templates', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('labels');
            $table->json('automations')->nullable()->after('custom_fields');
        });
    }

    public function down(): void
    {
        Schema::table('board_templates', function (Blueprint $table) {
            $table->dropColumn(['custom_fields', 'automations']);
        });
    }
};
