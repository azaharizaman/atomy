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
        Schema::create('accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('account_type'); // AccountType enum
            $table->string('currency', 3)->default('MYR');
            $table->ulid('parent_id')->nullable();
            $table->boolean('is_header')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('parent_id')
                ->references('id')
                ->on('accounts')
                ->nullOnDelete();

            // Indexes
            $table->index('account_type');
            $table->index('parent_id');
            $table->index('is_active');
            $table->index(['code', 'deleted_at']); // Unique code excluding soft-deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
