<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->ulid('user_id')->nullable()->index();
            $table->ulid('tenant_id')->nullable()->index();
            $table->json('payload');
            $table->timestamp('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
