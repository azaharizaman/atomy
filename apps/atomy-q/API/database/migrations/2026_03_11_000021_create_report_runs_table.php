<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('schedule_id')->nullable();
            $table->string('report_type')->index();
            $table->string('status')->default('running');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_path')->nullable();
            $table->json('parameters')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('schedule_id');
            $table->index(['tenant_id', 'status']);

            $table->foreign('schedule_id')
                ->references('id')
                ->on('report_schedules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
    }
};
