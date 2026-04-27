<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_submissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('rfq_id');
            $table->ulid('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->ulid('uploaded_by')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('status')->default('processing');
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamp('parsed_at')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->integer('line_items_count')->nullable();
            $table->integer('warnings_count')->nullable();
            $table->integer('errors_count')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'rfq_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'uploaded_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_submissions');
    }
};
