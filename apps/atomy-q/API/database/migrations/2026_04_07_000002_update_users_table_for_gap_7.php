<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->integer('failed_login_attempts')->default(0);
            $table->string('lockout_reason')->nullable();
            $table->timestamp('lockout_expires_at')->nullable();
            $table->boolean('mfa_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'failed_login_attempts',
                'lockout_reason',
                'lockout_expires_at',
                'mfa_enabled',
            ]);
        });
    }
};
