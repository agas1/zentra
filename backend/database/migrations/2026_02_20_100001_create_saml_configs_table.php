<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saml_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('idp_entity_id');
            $table->text('idp_sso_url');
            $table->text('idp_slo_url')->nullable();
            $table->text('idp_x509_cert');
            $table->string('domain');
            $table->boolean('sso_enforced')->default(false);
            $table->boolean('is_active')->default(false);
            $table->json('attribute_mapping')->nullable();
            $table->timestamps();

            $table->unique('workspace_id');
            $table->unique('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saml_configs');
    }
};
