<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('email');
            $table->string('name');
            $table->string('password_hash');
            $table->string('role')->default('user');
            $table->string('status')->default('active');
            $table->string('timezone')->default('UTC');
            $table->string('locale')->default('en');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->string('lockout_reason')->nullable();
            $table->timestamp('lockout_expires_at')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->timestamps();

            $table->unique('email');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
