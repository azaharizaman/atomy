<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One user belongs to one tenant; login is by email only. Emails must be globally unique.
 * Fails if duplicate emails exist across tenants — resolve data before migrating.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'email']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'email']);
        });
    }
};
