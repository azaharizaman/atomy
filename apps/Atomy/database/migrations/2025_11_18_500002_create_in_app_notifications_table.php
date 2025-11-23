<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('recipient_id')->index();
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable();
            $table->string('icon')->nullable();
            $table->string('priority'); // low, normal, high, critical
            $table->string('category'); // transactional, marketing, system, alert
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // // $table->index(['recipient_id', 'is_read']);
            // // $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_app_notifications');
    }
};
