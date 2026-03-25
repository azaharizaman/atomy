<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_approval_instances', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('template_id')->index();
            $table->string('workflow_instance_id', 256);
            $table->string('subject_type', 128);
            $table->string('subject_id', 256);
            $table->string('status', 32);
            $table->timestamps();

            $table->index(['tenant_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_approval_instances');
    }
};
