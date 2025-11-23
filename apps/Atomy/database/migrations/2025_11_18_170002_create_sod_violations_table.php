<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sod_violations', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('rule_id', 26)->index();
            $table->string('transaction_id', 26)->index(); // ID of the violating transaction
            $table->string('transaction_type', 50); // Type of transaction
            $table->string('creator_id', 26); // User who created the transaction
            $table->string('approver_id', 26); // User who attempted to approve
            $table->timestamp('violated_at')->index(); // When the violation occurred
            $table->timestamp('created_at');

            // Foreign key to SOD rules
            // $table->foreign('rule_id')->references('id')->on('sod_rules')->onDelete('cascade');

            // Index for reporting and queries
            // $table->index(['tenant_id', 'violated_at']);
            // $table->index(['tenant_id', 'rule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sod_violations');
    }
};
