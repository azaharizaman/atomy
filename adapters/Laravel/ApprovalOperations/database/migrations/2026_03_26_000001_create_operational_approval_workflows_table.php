<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_approval_workflows', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('operational_approval_instance_id')->index();
            $table->string('workflow_definition_id', 256);
            $table->string('subject_type', 128);
            $table->string('subject_id', 256);
            $table->string('current_state', 32);
            $table->ulid('last_actor_principal_id')->nullable();
            $table->text('last_comment')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'operational_approval_instance_id']);
            $table->foreign('operational_approval_instance_id')
                ->references('id')
                ->on('operational_approval_instances')
                ->cascadeOnDelete();
            $table->index(['tenant_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_approval_workflows');
    }
};
