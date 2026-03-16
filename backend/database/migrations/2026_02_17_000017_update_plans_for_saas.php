<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_members')->default(2)->after('active_demand_limit');
            $table->decimal('price_monthly', 10, 2)->default(0)->after('max_members');
            $table->boolean('is_default')->default(false)->after('price_monthly');

            // Remove revision_limit_per_demand (no longer needed)
            $table->dropColumn('revision_limit_per_demand');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('revision_limit_per_demand')->default(3);
            $table->dropColumn(['max_members', 'price_monthly', 'is_default']);
        });
    }
};
