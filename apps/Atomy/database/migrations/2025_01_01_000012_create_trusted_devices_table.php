<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('device_fingerprint')->unique();
            $table->string('device_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('trusted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            // // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // // $table->index(['user_id', 'device_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
