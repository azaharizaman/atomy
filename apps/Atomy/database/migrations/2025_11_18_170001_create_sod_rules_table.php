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
        Schema::create('sod_rules', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('rule_name', 100);
            $table->string('transaction_type', 50)->index(); // purchase_order, invoice, etc.
            $table->string('severity_level', 20); // Critical, High, Medium, Low
            $table->string('creator_role', 50); // Role that can create
            $table->string('approver_role', 50); // Role that can approve
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Index for quick lookups
            // $table->index(['tenant_id', 'transaction_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sod_rules');
    }
};
