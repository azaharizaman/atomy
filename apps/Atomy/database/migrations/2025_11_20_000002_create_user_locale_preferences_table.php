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
        Schema::create('user_locale_preferences', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26);
            $table->char('tenant_id', 26);
            $table->string('locale_code', 10);
            $table->string('timezone', 50)->default('UTC');
            $table->timestamps();

            // Composite unique key - one preference per user per tenant
            $table->unique(['user_id', 'tenant_id'], 'user_tenant_unique');

            // Foreign key to locales table
            $table->foreign('locale_code')
                ->references('code')
                ->on('locales')
                ->onDelete('restrict'); // Prevent deletion of locale if in use

            // Foreign keys to users and tenants tables
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // Index for fast lookups
            $table->index(['user_id', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locale_preferences');
    }
};
