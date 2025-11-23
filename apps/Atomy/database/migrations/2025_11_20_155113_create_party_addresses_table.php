<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('party_id')->index();
            $table->string('address_type', 20); // 'billing', 'shipping', 'legal', 'headquarters', etc.
            $table->json('postal_address'); // {street_line_1, street_line_2, street_line_3, city, district, state, postal_code, country}
            $table->boolean('is_primary')->default(false);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Foreign key
            // $table->foreign('party_id')
                  // ->references('id')
                  // ->on('parties')
                  // ->onDelete('cascade');
            
            // Indexes
            // $table->index(['party_id', 'address_type']);
            // $table->index(['party_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_addresses');
    }
};
