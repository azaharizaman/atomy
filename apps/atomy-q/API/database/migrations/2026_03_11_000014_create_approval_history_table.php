<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_history', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->foreignUlid('approval_id')->constrained('approvals')->cascadeOnDelete();
            $table->string('action');
            $table->ulid('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'approval_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_history');
    }
};
