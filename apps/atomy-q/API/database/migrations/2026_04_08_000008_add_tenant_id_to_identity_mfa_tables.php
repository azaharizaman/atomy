<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mfa_enrollments', function (Blueprint $table): void {
            $table->ulid('tenant_id')->nullable()->after('id')->index();
        });

        Schema::table('mfa_backup_codes', function (Blueprint $table): void {
            $table->ulid('tenant_id')->nullable()->after('id')->index();
        });

        // Backfill mfa_enrollments
        DB::table('mfa_enrollments')
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => DB::table('users')
                    ->whereColumn('users.id', 'mfa_enrollments.user_id')
                    ->select('tenant_id')
                    ->limit(1)
            ]);

        // Backfill mfa_backup_codes
        DB::table('mfa_backup_codes')
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => DB::table('users')
                    ->whereColumn('users.id', 'mfa_backup_codes.user_id')
                    ->select('tenant_id')
                    ->limit(1)
            ]);

        // Enforce invariants
        if (DB::table('mfa_enrollments')->whereNull('tenant_id')->exists()) {
            throw new \RuntimeException('Migration failed: Some mfa_enrollments could not be matched to a tenant.');
        }

        if (DB::table('mfa_backup_codes')->whereNull('tenant_id')->exists()) {
            throw new \RuntimeException('Migration failed: Some mfa_backup_codes could not be matched to a tenant.');
        }

        // Make non-nullable
        Schema::table('mfa_enrollments', function (Blueprint $table): void {
            $table->ulid('tenant_id')->nullable(false)->change();
        });

        Schema::table('mfa_backup_codes', function (Blueprint $table): void {
            $table->ulid('tenant_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('mfa_backup_codes', function (Blueprint $table): void {
            $table->dropColumn('tenant_id');
        });

        Schema::table('mfa_enrollments', function (Blueprint $table): void {
            $table->dropColumn('tenant_id');
        });
    }
};
