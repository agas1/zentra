<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'monthly_credit_limit')) {
                $table->dropColumn('monthly_credit_limit');
            }
            if (Schema::hasColumn('plans', 'active_demand_limit')) {
                $table->dropColumn('active_demand_limit');
            }
            if (!Schema::hasColumn('plans', 'max_boards')) {
                $table->integer('max_boards')->default(5);
            }
            if (!Schema::hasColumn('plans', 'max_storage_mb')) {
                $table->integer('max_storage_mb')->default(100);
            }
        });

        Schema::table('workspaces', function (Blueprint $table) {
            $columns = ['cycle_start_day', 'current_cycle_start', 'current_cycle_end', 'remaining_credits'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('workspaces', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('demand_messages');
        Schema::dropIfExists('demand_histories');
        Schema::dropIfExists('demands');
    }

    public function down(): void
    {
        // Not reversible — demands infrastructure removed
    }
};
