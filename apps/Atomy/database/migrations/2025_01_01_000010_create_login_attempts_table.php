<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->nullable();
            $table->string('email');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at')->useCurrent();

            // // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // // $table->index(['user_id', 'attempted_at']);
            // // $table->index(['user_id', 'successful']);
            // // $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
