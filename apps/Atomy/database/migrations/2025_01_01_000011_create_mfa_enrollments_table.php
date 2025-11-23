<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_enrollments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('method', 20);
            $table->text('secret')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // // $table->index(['user_id', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_enrollments');
    }
};
