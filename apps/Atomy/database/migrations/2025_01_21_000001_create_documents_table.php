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
        Schema::create('documents', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->char('tenant_id', 26)->index();
            $table->char('owner_id', 26)->index();
            $table->string('name');
            $table->string('storage_path')->unique();
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type');
            $table->string('type'); // Enum: contract, invoice, report, etc.
            $table->string('state')->default('active'); // Enum: active, archived, deleted
            $table->string('checksum_algo')->default('sha256');
            $table->string('checksum_value');
            $table->json('metadata')->nullable(); // Free-form metadata
            $table->json('tags')->nullable(); // Array of tags
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('purge_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            // // $table->index(['tenant_id', 'owner_id']);
            // // $table->index(['tenant_id', 'type']);
            // // $table->index(['tenant_id', 'state']);
            // // $table->index('created_at');
            // // $table->index('purge_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
