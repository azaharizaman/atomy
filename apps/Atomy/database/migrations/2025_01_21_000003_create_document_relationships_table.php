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
        Schema::create('document_relationships', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->char('source_document_id', 26);
            $table->char('target_document_id', 26);
            $table->string('type'); // Enum: parent_child, attachment, reference, superseded_by
            $table->text('description')->nullable();
            $table->char('created_by', 26);
            $table->timestamps();

            // Foreign keys
            $table->foreign('source_document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');

            $table->foreign('target_document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');

            // Unique constraint (prevent duplicate relationships)
            $table->unique(['source_document_id', 'target_document_id', 'type'], 'unique_relationship');

            // Indexes
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_relationships');
    }
};
