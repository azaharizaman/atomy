<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_acl', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('project_id');
            $table->ulid('user_id');
            $table->string('role'); // owner, manager, contributor, viewer, client_stakeholder
            $table->ulid('tenant_id')->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'project_id', 'user_id']);
            $table->index('project_id');
            $table->index('user_id');
            $table->index(['user_id', 'tenant_id']);
            $table->index(['project_id', 'role']);
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_acl');
    }
};
