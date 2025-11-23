<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_contact_methods', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('party_id')->index();
            $table->string('type', 20); // 'email', 'phone', 'mobile', 'fax', 'whatsapp', etc.
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Foreign key
            // $table->foreign('party_id')
                  // ->references('id')
                  // ->on('parties')
                  // ->onDelete('cascade');
            
            // Indexes
            // $table->index(['party_id', 'type']);
            // $table->index(['party_id', 'is_primary']);
            // $table->index(['type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_contact_methods');
    }
};
