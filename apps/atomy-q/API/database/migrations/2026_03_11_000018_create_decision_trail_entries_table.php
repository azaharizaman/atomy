<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_trail_entries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('comparison_run_id');
            $table->ulid('rfq_id');
            $table->integer('sequence');
            $table->string('event_type', 64);
            $table->string('payload_hash', 64);
            $table->string('previous_hash', 64);
            $table->string('entry_hash', 64);
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index('comparison_run_id');
            $table->index(['tenant_id', 'rfq_id']);
            $table->unique(['comparison_run_id', 'sequence'], 'decision_trail_run_seq_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_trail_entries');
    }
};
