<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_definitions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('policy_id', 256);
            $table->string('policy_version', 64);
            // JSON payload of a PolicyDefinition (decoded by Nexus\PolicyEngine\Services\JsonPolicyDecoder).
            $table->text('payload');
            $table->timestamps();

            $table->unique(['tenant_id', 'policy_id', 'policy_version']);
            $table->index(['tenant_id', 'policy_id', 'policy_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_definitions');
    }
};
