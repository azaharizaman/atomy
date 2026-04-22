<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_evidence', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('vendor_id')->index();
            $table->string('domain', 32);
            $table->string('type', 64);
            $table->string('title');
            $table->string('source')->default('manual');
            $table->timestamp('observed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('review_status', 32)->default('pending');
            $table->string('reviewed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'vendor_id', 'domain']);
            $table->index(['tenant_id', 'vendor_id', 'review_status']);
            $table->index(['tenant_id', 'expires_at']);
        });

        Schema::create('vendor_findings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('vendor_id')->index();
            $table->string('domain', 32);
            $table->string('issue_type', 64);
            $table->string('severity', 32);
            $table->string('status', 32)->default('open');
            $table->timestamp('opened_at')->nullable();
            $table->string('opened_by')->nullable();
            $table->string('remediation_owner')->nullable();
            $table->timestamp('remediation_due_at')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'vendor_id', 'domain']);
            $table->index(['tenant_id', 'vendor_id', 'status']);
            $table->index(['tenant_id', 'severity']);
            $table->index(['tenant_id', 'remediation_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_findings');
        Schema::dropIfExists('vendor_evidence');
    }
};
