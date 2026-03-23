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
            $table->string('error_code', 50)->nullable()->after('status');
            $table->text('error_message')->nullable()->after('error_code');
            $table->timestamp('processing_started_at')->nullable()->after('error_message');
            $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at');
            $table->timestamp('parsed_at')->nullable()->after('processing_completed_at');
            $table->tinyInteger('retry_count')->default(0)->after('parsed_at');
        });
    }

    public function down(): void
    {
        Schema::table('quote_submissions', function (Blueprint $table): void {
            $table->dropColumn([
                'error_code',
                'error_message',
                'processing_started_at',
                'processing_completed_at',
                'parsed_at',
                'retry_count',
            ]);
        });
    }
};