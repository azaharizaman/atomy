<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('number')->unique();
            $table->string('requester_id')->index();
            $table->text('description');
            $table->string('department');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'converted'])->default('draft')->index();
            $table->decimal('total_estimate', 19, 4)->default(0);
            $table->string('approver_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejector_id')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_converted')->default(false)->index();
            $table->string('converted_po_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // $table->index(['tenant_id', 'status']);
            // $table->index(['tenant_id', 'requester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
