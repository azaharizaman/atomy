<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('title');
            $table->text('description')->default('');
            $table->string('status')->default('pending');
            $table->string('priority')->default('medium');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->ulid('project_id')->nullable()->index();
            $table->json('assignee_ids')->nullable(); // list of user IDs
            $table->json('predecessor_ids')->nullable(); // list of task IDs
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
