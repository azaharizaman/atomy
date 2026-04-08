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

        DB::statement('
            UPDATE mfa_enrollments
            SET tenant_id = (
                SELECT users.tenant_id
                FROM users
                WHERE users.id = mfa_enrollments.user_id
            )
            WHERE tenant_id IS NULL
        ');

        DB::statement('
            UPDATE mfa_backup_codes
            SET tenant_id = (
                SELECT users.tenant_id
                FROM users
                WHERE users.id = mfa_backup_codes.user_id
            )
            WHERE tenant_id IS NULL
        ');
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
