<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('handler_type', 255)->index();
            $table->string('mode', 20)->index(); // CREATE_ONLY, UPDATE_ONLY, etc.
            $table->string('status', 20)->index(); // PENDING, PROCESSING, COMPLETED, FAILED
            $table->string('original_file_name', 500);
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);
            $table->timestampTz('uploaded_at');
            $table->string('uploaded_by', 100)->index();
            $table->string('tenant_id', 100)->index();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'uploaded_at']);
            $table->index(['status', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
