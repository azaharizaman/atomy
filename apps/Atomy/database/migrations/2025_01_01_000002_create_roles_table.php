<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->ulid('parent_role_id')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('requires_mfa')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // // $table->unique(['tenant_id', 'name']);
            // // $table->foreign('parent_role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
