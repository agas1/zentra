<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'slug')) {
                $table->string('slug', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('plans', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('plans', 'max_labels')) {
                $table->integer('max_labels')->default(6)->after('max_storage_mb');
            }
            if (!Schema::hasColumn('plans', 'max_attachment_size_mb')) {
                $table->integer('max_attachment_size_mb')->default(5)->after('max_labels');
            }
            if (!Schema::hasColumn('plans', 'features')) {
                $table->json('features')->nullable()->after('max_attachment_size_mb');
            }
            if (!Schema::hasColumn('plans', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });

        // Populate slug for existing plans
        $plans = DB::table('plans')->whereNull('slug')->get();
        foreach ($plans as $plan) {
            DB::table('plans')->where('id', $plan->id)->update([
                'slug' => Str::slug($plan->name),
            ]);
        }

        // Now make slug NOT NULL and unique
        Schema::table('plans', function (Blueprint $table) {
            $table->string('slug', 50)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['slug', 'description', 'max_labels', 'max_attachment_size_mb', 'features', 'sort_order']);
        });
    }
};
