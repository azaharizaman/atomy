<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('name');
            $table->string('client_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->ulid('project_manager_id');
            $table->string('status')->default('planning');
            $table->string('budget_type')->default('time_and_materials');
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
