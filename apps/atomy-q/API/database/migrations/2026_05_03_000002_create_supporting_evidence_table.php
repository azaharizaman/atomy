<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supporting_evidence', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('rfq_id');
            $table->ulid('vendor_id')->nullable();
            $table->ulid('quote_submission_id')->nullable();
            $table->ulid('award_id')->nullable();
            $table->text('reason');
            $table->string('original_filename');
            $table->string('file_type')->nullable();
            $table->string('storage_path');
            $table->string('checksum', 64);
            $table->ulid('uploaded_by')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['tenant_id', 'rfq_id']);
            $table->index(['tenant_id', 'quote_submission_id']);
            $table->index(['tenant_id', 'award_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supporting_evidence');
    }
};
