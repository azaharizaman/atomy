<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main sequences table
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('name');
            $table->string('scope_identifier')->nullable()->index();
            $table->string('pattern');
            $table->string('reset_period')->default('never');
            $table->integer('step_size')->default(1);
            $table->integer('reset_limit')->nullable();
            $table->string('gap_policy')->default('allow_gaps');
            $table->string('overflow_behavior')->default('throw_exception');
            $table->integer('exhaustion_threshold')->default(90);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // // $table->unique(['name', 'scope_identifier'], 'idx_sequences_name_scope');
            // // $table->index(['is_active', 'is_locked']);
        });

        // Counter state table
        Schema::create('sequence_counters', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('sequence_id', 26);
            $table->bigInteger('current_value')->default(0);
            $table->integer('generation_count')->default(0);
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();

            // // $table->foreign('sequence_id')->references('id')->on('sequences')->onDelete('cascade');
            // // $table->unique('sequence_id', 'idx_counters_sequence_lock');
        });

        // Reservations table
        Schema::create('sequence_reservations', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('sequence_id', 26);
            $table->uuid('reservation_id');
            $table->string('number');
            $table->string('status')->default('reserved');
            $table->timestamp('expires_at');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            // // $table->foreign('sequence_id')->references('id')->on('sequences')->onDelete('cascade');
            // // $table->index(['sequence_id', 'status']);
            // // $table->index(['expires_at', 'status'], 'idx_reservations_expires_at');
            // // $table->index(['reservation_id']);
        });

        // Gaps table
        Schema::create('sequence_gaps', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('sequence_id', 26);
            $table->string('number');
            $table->string('status')->default('unfilled');
            $table->string('reason')->nullable();
            $table->timestamp('filled_at')->nullable();
            $table->timestamps();

            // // $table->foreign('sequence_id')->references('id')->on('sequences')->onDelete('cascade');
            // // $table->index(['sequence_id', 'status']);
        });

        // Pattern versions table
        Schema::create('sequence_pattern_versions', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('sequence_id', 26);
            $table->string('pattern');
            $table->timestamp('effective_from');
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();

            // // $table->foreign('sequence_id')->references('id')->on('sequences')->onDelete('cascade');
            // // $table->index(['sequence_id', 'effective_from', 'effective_until']);
        });

        // Audit log table for sequence operations
        Schema::create('sequence_audits', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('sequence_id', 26);
            $table->string('event_type'); // pattern_created, pattern_modified, counter_reset, etc.
            $table->text('event_data'); // JSON payload with event details
            $table->string('performed_by')->nullable(); // User identifier
            $table->timestamps();

            // // $table->foreign('sequence_id')->references('id')->on('sequences')->onDelete('cascade');
            // // $table->index(['sequence_id', 'event_type']);
            // // $table->index(['sequence_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_audits');
        Schema::dropIfExists('sequence_pattern_versions');
        Schema::dropIfExists('sequence_gaps');
        Schema::dropIfExists('sequence_reservations');
        Schema::dropIfExists('sequence_counters');
        Schema::dropIfExists('sequences');
    }
};
