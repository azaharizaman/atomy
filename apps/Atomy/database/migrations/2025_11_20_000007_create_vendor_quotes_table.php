<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_quotes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('rfq_number')->index();
            // $table->foreignUlid('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->string('vendor_id')->index();
            $table->string('quote_reference');
            $table->date('quoted_date');
            $table->date('valid_until');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->index();
            $table->string('payment_terms')->nullable();
            $table->string('delivery_terms')->nullable();
            $table->text('notes')->nullable();
            $table->string('accepted_by')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('lines')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // $table->index(['tenant_id', 'status']);
            // $table->index(['tenant_id', 'vendor_id']);
            // $table->unique(['tenant_id', 'rfq_number', 'vendor_id', 'quote_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_quotes');
    }
};
