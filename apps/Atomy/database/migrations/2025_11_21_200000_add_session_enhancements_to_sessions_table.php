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
        Schema::table('sessions', function (Blueprint $table) {
            // Add tenant_id for multi-tenancy isolation
            $table->ulid('tenant_id')->nullable()->after('user_id');
            
            // Add device fingerprint for device tracking
            $table->string('device_fingerprint', 64)->nullable()->after('metadata');
            
            // Add geographic location (IP geolocation data)
            $table->json('geographic_location')->nullable()->after('device_fingerprint');
            
            // Add last activity timestamp for inactivity tracking
            $table->timestamp('last_activity_at')->nullable()->after('geographic_location');
            
            // Add indexes for performance
            // $table->index('tenant_id');
            // $table->index('device_fingerprint');
            // $table->index('last_activity_at');
            
            // Composite index for user + device queries
            // $table->index(['user_id', 'device_fingerprint']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'device_fingerprint']);
            $table->dropIndex(['device_fingerprint']);
            $table->dropIndex(['last_activity_at']);
            $table->dropIndex('tenant_id');
            
            $table->dropColumn([
                'tenant_id',
                'device_fingerprint',
                'geographic_location',
                'last_activity_at',
            ]);
        });
    }
};
