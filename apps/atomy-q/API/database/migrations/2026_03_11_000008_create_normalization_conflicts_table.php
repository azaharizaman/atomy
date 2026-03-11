<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normalization_conflicts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('normalization_source_line_id');
            $table->string('conflict_type');
            $table->string('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->ulid('resolved_by')->nullable();
            $table->timestamps();

            $table->index('normalization_source_line_id');
            $table->index(['tenant_id', 'resolution']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normalization_conflicts');
    }
};
