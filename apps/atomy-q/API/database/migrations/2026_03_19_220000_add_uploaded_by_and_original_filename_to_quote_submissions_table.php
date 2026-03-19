<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_submissions', function (Blueprint $table): void {
            $table->ulid('uploaded_by')->nullable()->after('vendor_name');
            $table->string('original_filename')->nullable()->after('file_type');

            $table->index(['tenant_id', 'uploaded_by']);
        });
    }

    public function down(): void
    {
        Schema::table('quote_submissions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'uploaded_by']);
            $table->dropColumn(['uploaded_by', 'original_filename']);
        });
    }
};
