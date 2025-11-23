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
            $table->string('status', 20)->default('draft');
            $table->text('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->ulid('period_id')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('posted_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversed_by')->nullable();
            $table->ulid('reversal_of_id')->nullable();
            $table->timestamps();

            // // $table->index('entry_number');
            // // $table->index('entry_date');
            // // $table->index('status');
            // // $table->index('period_id');
            // // $table->index('reversal_of_id');
            // // $table->index(['status', 'entry_date']);
            // // $table->index(['period_id', 'status']);

            // // $table->foreign('period_id')
                // // ->references('id')
                // // ->on('periods')
                // // ->onDelete('restrict');

            // // $table->foreign('reversal_of_id')
                // // ->references('id')
                // // ->on('journal_entries')
                // // ->onDelete('restrict');
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
