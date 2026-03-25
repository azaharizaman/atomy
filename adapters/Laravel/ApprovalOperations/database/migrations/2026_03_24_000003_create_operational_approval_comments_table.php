<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_approval_comments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('instance_id')->index();
            $table->string('author_principal_id', 256);
            $table->text('body');
            $table->string('attachment_storage_key', 512)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'instance_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_approval_comments');
    }
};
