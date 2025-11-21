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
        Schema::table('trusted_devices', function (Blueprint $table) {
            // Add tenant_id for multi-tenancy isolation
            $table->ulid('tenant_id')->nullable()->after('user_id');
            
            // Add trust status flag
            $table->boolean('is_trusted')->default(false)->after('device_name');
            
            // Add geographic location tracking
            $table->json('geographic_location')->nullable()->after('user_agent');
            
            // Add metadata for device characteristics
            $table->json('metadata')->nullable()->after('geographic_location');
            
            // Add trusted timestamp
            $table->timestamp('trusted_at')->nullable()->after('metadata');
            
            // Add last used timestamp
            $table->timestamp('last_used_at')->nullable()->after('trusted_at');
            
            // Add indexes for performance
            $table->index('tenant_id');
            $table->index(['user_id', 'is_trusted']);
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trusted_devices', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_trusted']);
            $table->dropIndex(['last_used_at']);
            $table->dropIndex('tenant_id');
            
            $table->dropColumn([
                'tenant_id',
                'is_trusted',
                'geographic_location',
                'metadata',
                'trusted_at',
                'last_used_at',
            ]);
        });
    }
};
