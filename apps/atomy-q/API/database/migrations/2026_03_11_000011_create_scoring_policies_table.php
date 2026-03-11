<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_policies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('scoring_model_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('weights')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('scoring_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_policies');
    }
};
