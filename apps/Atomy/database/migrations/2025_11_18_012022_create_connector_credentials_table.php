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
        Schema::create('connector_credentials', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->nullable()->index();
            $table->string('service_name', 100)->index();
            $table->string('auth_method', 20);
            $table->text('credential_data'); // Encrypted JSON
            $table->timestamp('expires_at')->nullable()->index();
            $table->text('refresh_token')->nullable(); // Encrypted
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            // Ensure unique service per tenant
            $table->unique(['service_name', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connector_credentials');
    }
};
