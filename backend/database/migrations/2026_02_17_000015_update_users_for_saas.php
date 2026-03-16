<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove old single-tenant columns
            $table->dropForeign(['client_id']);
            $table->dropColumn(['role', 'client_id', 'is_active']);

            // Add SaaS columns
            $table->string('google_id')->nullable()->after('email');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->timestamp('email_verified_at')->nullable()->after('avatar_url');

            // Password nullable for Google OAuth users
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar_url', 'email_verified_at']);
            $table->string('role', 20)->default('member');
            $table->foreignUuid('client_id')->nullable()->constrained('clients');
            $table->boolean('is_active')->default(true);
            $table->string('password')->nullable(false)->change();
        });
    }
};
