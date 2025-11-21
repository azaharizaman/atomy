<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asset Categories
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('depreciation_method')->default('straight_line');
            $table->integer('default_useful_life_months')->nullable();
            $table->decimal('default_salvage_rate', 5, 2)->default(0)->comment('Percentage 0-100');
            $table->timestamps();
            
            $table->index('code');
        });

        // Assets (main table with hybrid location field)
        Schema::create('assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('asset_tag')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignUlid('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            
            // Financial fields
            $table->decimal('cost', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->date('acquisition_date');
            $table->string('depreciation_method');
            $table->integer('useful_life_months');
            
            // Units of Production (Tier 3)
            $table->decimal('total_expected_units', 15, 2)->nullable();
            $table->string('unit_type')->nullable()->comment('e.g., machine_hours, miles, units_produced');
            
            // Location (Hybrid for backward compatibility)
            $table->string('location')->nullable()->comment('Tier 1: string location');
            $table->foreignUlid('location_id')->nullable()->comment('Tier 2/3: FK to locations table');
            
            // Status
            $table->string('status')->default('active');
            $table->date('disposal_date')->nullable();
            $table->string('disposal_method')->nullable();
            $table->decimal('disposal_proceeds', 15, 2)->nullable();
            $table->text('disposal_notes')->nullable();
            
            // Warranty (Tier 2)
            $table->date('warranty_expiry')->nullable();
            $table->string('warranty_provider')->nullable();
            
            // Multi-currency (Tier 3)
            $table->string('currency_code', 3)->default('MYR');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'depreciation_method']);
            $table->index('acquisition_date');
            $table->index('category_id');
            $table->index('location_id');
        });

        // Depreciation Records
        Schema::create('depreciation_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('asset_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('depreciation_amount', 15, 2);
            $table->string('method');
            $table->decimal('accumulated_before', 15, 2);
            $table->decimal('accumulated_after', 15, 2);
            $table->decimal('book_value_after', 15, 2);
            
            // Units tracking (Tier 3)
            $table->decimal('units_consumed', 15, 2)->nullable();
            
            // GL Integration (Tier 3)
            $table->foreignUlid('journal_entry_id')->nullable()->comment('Link to posted JE');
            
            $table->timestamps();
            
            $table->index(['asset_id', 'period_start']);
            $table->unique(['asset_id', 'period_start', 'period_end']);
        });

        // Maintenance Records (Tier 2)
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('asset_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // preventive, corrective, emergency, upgrade
            $table->text('description');
            $table->date('scheduled_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->string('vendor')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('performed_by')->nullable()->comment('User ID who performed');
            $table->timestamps();
            
            $table->index(['asset_id', 'type']);
            $table->index('scheduled_date');
        });

        // Warranty Records (Tier 2)
        Schema::create('warranty_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('asset_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->date('start_date');
            $table->date('expiry_date');
            $table->string('coverage_type'); // full, parts_only, labor_only
            $table->text('terms')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->timestamps();
            
            $table->index('expiry_date');
        });

        // Physical Audit Logs (Tier 3)
        Schema::create('physical_audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('audit_id')->index();
            $table->date('initiated_at');
            $table->date('completed_at')->nullable();
            $table->string('status'); // in_progress, completed, cancelled
            $table->json('scope')->comment('Filters: location_ids, category_ids, etc.');
            $table->integer('total_assets')->default(0);
            $table->integer('total_verified')->default(0);
            $table->integer('total_discrepancies')->default(0);
            $table->decimal('accuracy_rate', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('physical_audit_verifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('audit_id');
            $table->foreignUlid('asset_id')->constrained();
            $table->timestamp('verified_at');
            $table->boolean('location_match')->default(false);
            $table->string('expected_location')->nullable();
            $table->string('actual_location')->nullable();
            $table->string('condition')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('verified_by')->nullable()->comment('User ID');
            $table->timestamps();
            
            $table->index(['audit_id', 'asset_id']);
        });

        Schema::create('physical_audit_discrepancies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('audit_id');
            $table->foreignUlid('asset_id')->nullable();
            $table->string('asset_tag')->nullable();
            $table->string('type'); // missing_asset, extra_asset, location_mismatch
            $table->string('expected_location')->nullable();
            $table->string('actual_location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();
            
            $table->index(['audit_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_audit_discrepancies');
        Schema::dropIfExists('physical_audit_verifications');
        Schema::dropIfExists('physical_audit_logs');
        Schema::dropIfExists('warranty_records');
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('depreciation_records');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
