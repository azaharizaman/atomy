<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('tenant_id', 26);
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->char('gl_asset_account_id', 26);
            $table->char('gl_depreciation_account_id', 26);
            $table->char('gl_accumulated_depreciation_account_id', 26);
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'sum_of_years_digits', 'units_of_production']);
            $table->integer('useful_life_months');
            $table->decimal('salvage_value_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->foreign('gl_asset_account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('gl_depreciation_account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('gl_accumulated_depreciation_account_id')->references('id')->on('accounts')->onDelete('restrict');
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('tenant_id', 26);
            $table->char('asset_category_id', 26);
            $table->string('asset_tag', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'disposed', 'under_maintenance', 'retired'])->default('active');
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 20, 4);
            $table->decimal('salvage_value', 20, 4)->default(0);
            $table->decimal('accumulated_depreciation', 20, 4)->default(0);
            $table->decimal('book_value', 20, 4);
            $table->date('depreciation_start_date');
            $table->integer('useful_life_months');
            $table->string('location', 255)->nullable();
            $table->char('custodian_employee_id', 26)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->date('disposal_date')->nullable();
            $table->enum('disposal_method', ['sale', 'donation', 'scrap', 'trade_in', 'other'])->nullable();
            $table->decimal('disposal_proceeds', 20, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('asset_category_id');
            $table->index('acquisition_date');
            $table->foreign('asset_category_id')->references('id')->on('asset_categories')->onDelete('restrict');
        });

        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('asset_id', 26);
            $table->date('period_date');
            $table->decimal('depreciation_amount', 20, 4);
            $table->decimal('accumulated_depreciation', 20, 4);
            $table->decimal('book_value', 20, 4);
            $table->char('gl_journal_entry_id', 26)->nullable();
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'period_date']);
            $table->unique(['asset_id', 'period_date']);
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('gl_journal_entry_id')->references('id')->on('journal_entries')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
