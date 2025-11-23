<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for analytics_permissions table
 * Manages RBAC for analytics queries
 * Satisfies: SEC-ANA-0480, SEC-ANA-0485 (RBAC integration, prevent unauthorized execution)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Query reference
            $table->uuid('query_id')->index();
            
            // Permission subject (user or role)
            $table->string('subject_type')->index(); // user, role
            $table->string('subject_id');
            // $table->index(['subject_type', 'subject_id'], 'analytics_permissions_subject_index');
            
            // Actions granted
            $table->json('actions'); // ['execute', 'view', 'modify', 'delete']
            
            // Delegation chain support (BUS-ANA-0139, BUS-ANA-0143)
            $table->string('delegated_by')->nullable();
            $table->integer('delegation_level')->default(0); // Max 3 levels
            $table->timestamp('delegation_expires_at')->nullable();
            
            // Metadata
            $table->string('granted_by')->nullable();
            $table->timestamps();
            
            // Indexes
            // $table->index(['query_id', 'subject_type', 'subject_id'], 'query_subject_index');
            // $table->index('delegation_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_permissions');
    }
};
