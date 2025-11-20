<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('party_type', 20)->index(); // 'individual' or 'organization'
            $table->string('legal_name');
            $table->string('trading_name')->nullable();
            $table->json('tax_identity')->nullable(); // {country, number, issue_date, expiry_date, type}
            $table->date('date_of_birth')->nullable(); // For individuals only
            $table->date('registration_date')->nullable(); // For organizations only
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['tenant_id', 'legal_name']);
            $table->index(['tenant_id', 'party_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
