<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_relationships', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('from_party_id')->index();
            $table->ulid('to_party_id')->index();
            $table->string('relationship_type', 30); // 'employment_at', 'contact_for', 'subsidiary_of', etc.
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->string('role')->nullable(); // e.g., 'CEO', 'Primary Contact', 'Finance Manager'
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Foreign keys
            // $table->foreign('from_party_id')
                  // ->references('id')
                  // ->on('parties')
                  // ->onDelete('cascade');
            
            // $table->foreign('to_party_id')
                  // ->references('id')
                  // ->on('parties')
                  // ->onDelete('cascade');
            
            // Indexes
            // $table->index(['tenant_id', 'from_party_id']);
            // $table->index(['tenant_id', 'to_party_id']);
            // $table->index(['from_party_id', 'relationship_type']);
            // $table->index(['to_party_id', 'relationship_type']);
            // $table->index('effective_from');
            // $table->index('effective_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_relationships');
    }
};
