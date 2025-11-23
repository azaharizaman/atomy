<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Scope information
            $table->string('scope', 20)->index(); // 'user', 'tenant', 'application'
            $table->string('scope_id')->nullable()->index(); // user_id, tenant_id, or null for application

            // Setting data
            $table->string('key')->index();
            $table->json('value');
            $table->string('type', 20)->default('string'); // string, int, bool, float, array, json

            // Metadata
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable();
            $table->string('group')->nullable()->index(); // Logical grouping (e.g., 'mail', 'api', 'ui')

            // Flags
            $table->boolean('is_readonly')->default(false);
            $table->boolean('is_protected')->default(false);
            $table->boolean('is_encrypted')->default(false);

            // Audit fields
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: scope + scope_id + key
            // // $table->unique(['scope', 'scope_id', 'key'], 'settings_scope_key_unique');

            // Indexes for performance
            // // $table->index(['scope', 'scope_id'], 'settings_scope_lookup');
            // // $table->index(['key', 'scope'], 'settings_key_scope_lookup');
            // // $table->index('group', 'settings_group_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
