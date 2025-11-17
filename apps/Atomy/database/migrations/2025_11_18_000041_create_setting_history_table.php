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
        Schema::create('setting_history', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Reference to setting
            $table->ulid('setting_id')->index();
            $table->foreign('setting_id')
                ->references('id')
                ->on('settings')
                ->cascadeOnDelete();

            // Scope information (denormalized for easier querying)
            $table->string('scope', 20);
            $table->string('scope_id')->nullable();
            $table->string('key');

            // Change tracking
            $table->string('action', 20); // 'created', 'updated', 'deleted'
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();

            // Audit information
            $table->string('changed_by')->nullable(); // User ID who made the change
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            // Indexes for history queries
            $table->index(['setting_id', 'changed_at'], 'history_setting_time');
            $table->index(['scope', 'scope_id'], 'history_scope_lookup');
            $table->index('key', 'history_key_lookup');
            $table->index('changed_by', 'history_actor_lookup');
            $table->index('changed_at', 'history_time_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_history');
    }
};
