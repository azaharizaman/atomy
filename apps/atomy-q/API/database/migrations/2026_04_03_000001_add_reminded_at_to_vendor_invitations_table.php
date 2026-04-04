<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_invitations', function (Blueprint $table): void {
            if (! Schema::hasColumn('vendor_invitations', 'reminded_at')) {
                $table->timestamp('reminded_at')->nullable()->after('responded_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_invitations', function (Blueprint $table): void {
            if (Schema::hasColumn('vendor_invitations', 'reminded_at')) {
                $table->dropColumn('reminded_at');
            }
        });
    }
};
