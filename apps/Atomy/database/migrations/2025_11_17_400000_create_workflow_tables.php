<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Workflow definitions table
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('version', 50);
            $table->json('states'); // Array of state definitions
            $table->json('transitions'); // Array of transition definitions
            $table->string('initial_state');
            $table->json('data_schema')->nullable(); // JSON schema for validation
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // // $table->index(['is_active']);
        });

        // Workflow instances table
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('definition_id')->constrained('workflow_definitions');
            $table->string('current_state');
            $table->string('subject_type'); // Polymorphic: model class name
            $table->ulid('subject_id'); // Polymorphic: model ID
            $table->json('data')->nullable(); // Workflow data
            $table->string('status', 50)->default('active'); // active, completed, cancelled, suspended, failed
            $table->boolean('is_locked')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // // $table->index(['definition_id']);
            // // $table->index(['current_state']);
            // // $table->index(['subject_type', 'subject_id']);
            // // $table->index(['status']);
            // // $table->index(['is_locked']);
        });

        // Workflow history (audit trail)
        Schema::create('workflow_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('workflow_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->string('transition')->nullable();
            $table->string('from_state');
            $table->string('to_state');
            $table->ulid('actor_id')->nullable(); // User who triggered transition
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            // // $table->index(['workflow_id']);
            // // $table->index(['actor_id']);
            // // $table->index(['created_at']);
        });

        // Workflow tasks table
        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('workflow_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->string('state_name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->ulid('assigned_user_id')->nullable();
            $table->string('assigned_role')->nullable();
            $table->string('status', 50)->default('pending'); // pending, in_progress, completed, cancelled
            $table->string('priority', 50)->default('medium'); // low, medium, high, critical
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->ulid('completed_by')->nullable();
            $table->string('action', 50)->nullable(); // approve, reject, request_changes, etc.
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // // $table->index(['workflow_id']);
            // // $table->index(['assigned_user_id']);
            // // $table->index(['assigned_role']);
            // // $table->index(['status']);
            // // $table->index(['priority']);
            // // $table->index(['due_at']);
        });

        // Workflow delegations table
        Schema::create('workflow_delegations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('delegator_id'); // User who delegates
            $table->ulid('delegatee_id'); // User who receives delegation
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->integer('chain_depth')->default(1);
            $table->timestamps();

            // // $table->index(['delegator_id']);
            // // $table->index(['delegatee_id']);
            // // $table->index(['is_active']);
            // // $table->index(['starts_at', 'ends_at']);
        });

        // Workflow timers table
        Schema::create('workflow_timers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('workflow_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->string('type', 50); // escalation, sla_check, reminder, scheduled_task
            $table->timestamp('trigger_at');
            $table->json('action'); // Action definition
            $table->boolean('is_fired')->default(false);
            $table->timestamp('fired_at')->nullable();
            $table->timestamps();

            // // $table->index(['workflow_id']);
            // // $table->index(['type']);
            // // $table->index(['trigger_at', 'is_fired']);
        });

        // Approval matrix table (optional, for threshold-based routing)
        Schema::create('approval_matrices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('workflow_definition_id')->nullable();
            $table->json('rules'); // Array of threshold rules
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // // $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_matrices');
        Schema::dropIfExists('workflow_timers');
        Schema::dropIfExists('workflow_delegations');
        Schema::dropIfExists('workflow_tasks');
        Schema::dropIfExists('workflow_history');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definitions');
    }
};
