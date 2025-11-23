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
        Schema::create('compliance_schemes', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('scheme_name', 100)->index(); // ISO14001, SOX, GDPR, etc.
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('activated_at')->nullable();
            $table->json('configuration')->nullable(); // Scheme-specific configuration
            $table->timestamps();

            // Ensure one active scheme per tenant
            // $table->unique(['tenant_id', 'scheme_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_schemes');
    }
};
