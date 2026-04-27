<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('registration_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('legal_name');
            $table->string('display_name');
            $table->string('country_of_registration');
            $table->string('primary_contact_name');
            $table->string('primary_contact_email');
            $table->string('primary_contact_phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('onboarded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->ulid('approved_by_user_id')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'display_name']);
            $table->index(['tenant_id', 'primary_contact_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
