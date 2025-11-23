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
        Schema::create('encryption_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key_id', 191)->index()->comment('Unique key identifier (e.g., tenant-123-finance)');
            $table->text('encrypted_key')->comment('Key material encrypted with master key (envelope encryption)');
            $table->string('algorithm', 50)->comment('Encryption algorithm (aes-256-gcm, chacha20-poly1305, etc.)');
            $table->unsignedInteger('version')->default(1)->comment('Key version for rotation tracking');
            $table->timestamp('created_at')->comment('When key was created');
            $table->timestamp('expires_at')->nullable()->comment('When key expires (null = never)');
            $table->timestamp('updated_at');
            
            // Composite index for key_id + version (latest version first)
            // $table->index(['key_id', 'version']);
            
            // Index for finding expiring keys
            // $table->index('expires_at');
        });
        
        Schema::create('key_rotation_history', function (Blueprint $table) {
            $table->id();
            $table->string('key_id', 191)->index()->comment('Key that was rotated');
            $table->unsignedInteger('old_version')->comment('Previous key version');
            $table->unsignedInteger('new_version')->comment('New key version');
            $table->timestamp('rotated_at')->comment('When rotation occurred');
            $table->string('reason', 100)->default('automated_rotation')->comment('Rotation reason');
            $table->string('scheduled_job_id', 26)->nullable()->comment('Scheduler job ID (ULID) if automated');
            $table->text('notes')->nullable()->comment('Additional rotation notes');
            
            // Index for audit queries
            // $table->index(['key_id', 'rotated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_rotation_history');
        Schema::dropIfExists('encryption_keys');
    }
};
