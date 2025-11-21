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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->char('document_id', 26);
            $table->unsignedInteger('version_number');
            $table->string('storage_path')->unique();
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum_algo')->default('sha256');
            $table->string('checksum_value');
            $table->char('created_by', 26);
            $table->text('change_description')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');

            // Unique constraint
            $table->unique(['document_id', 'version_number']);

            // Indexes
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
