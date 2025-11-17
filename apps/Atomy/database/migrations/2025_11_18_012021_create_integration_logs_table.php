<?php

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
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->nullable()->index();
            $table->string('service_name', 100)->index();
            $table->string('endpoint', 500);
            $table->string('method', 10);
            $table->string('status', 20)->index();
            $table->integer('http_status_code')->nullable();
            $table->integer('duration_ms')->index();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempt_number')->default(1);
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at');

            // Composite indexes for common queries
            $table->index(['service_name', 'created_at']);
            $table->index(['service_name', 'status', 'created_at']);
            $table->index(['tenant_id', 'service_name', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
