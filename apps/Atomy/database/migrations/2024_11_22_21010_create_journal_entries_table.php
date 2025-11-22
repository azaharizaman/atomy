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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date');
            $table->string('reference', 100)->nullable();
            $table->text('description');
            $table->string('status'); // JournalEntryStatus enum
            $table->ulid('created_by');
            $table->timestamp('posted_at')->nullable();
            $table->ulid('posted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('entry_number');
            $table->index('entry_date');
            $table->index('status');
            $table->index('created_by');
            $table->index(['entry_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
