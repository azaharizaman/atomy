<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_approval_templates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('subject_type', 128);
            $table->string('workflow_definition_id', 256);
            $table->string('policy_id', 256);
            $table->string('policy_version', 64);
            $table->unsignedInteger('template_version')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'subject_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_approval_templates');
    }
};
