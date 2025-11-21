<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Service Contracts
        Schema::create('service_contracts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('contract_number')->unique();
            $table->foreignUlid('customer_party_id')->constrained('parties');
            $table->foreignUlid('asset_id')->nullable()->constrained('assets');
            $table->string('status')->index(); // ContractStatus enum
            $table->date('start_date');
            $table->date('end_date')->index();
            $table->string('response_time'); // "4 hours", "24 hours"
            $table->integer('maintenance_interval_days')->nullable();
            $table->decimal('contract_value', 15, 2);
            $table->string('currency', 3)->default('MYR');
            $table->json('covered_services')->nullable(); // Array of ServiceType
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'status']);
        });

        // Work Orders
        Schema::create('work_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique(); // WO-2025-00001
            $table->foreignUlid('customer_party_id')->constrained('parties');
            $table->foreignUlid('service_location_id')->nullable()->constrained('postal_addresses');
            $table->foreignUlid('asset_id')->nullable()->constrained('assets');
            $table->foreignUlid('service_contract_id')->nullable()->constrained('service_contracts');
            $table->foreignUlid('assigned_technician_id')->nullable()->constrained('staff');
            $table->string('status')->index(); // WorkOrderStatus enum
            $table->string('priority'); // WorkOrderPriority enum
            $table->string('service_type'); // ServiceType enum
            $table->text('description');
            $table->dateTime('scheduled_start')->nullable()->index();
            $table->dateTime('scheduled_end')->nullable();
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->dateTime('sla_deadline')->nullable()->index();
            $table->text('technician_notes')->nullable();
            $table->decimal('labor_hours', 5, 2)->nullable();
            $table->decimal('labor_cost', 15, 2)->nullable();
            $table->string('labor_currency', 3)->default('MYR');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['assigned_technician_id', 'scheduled_start']);
        });

        // Checklist Templates
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('service_type'); // ServiceType enum
            $table->json('items'); // [{'id': '1', 'label': 'Check voltage', 'type': 'critical', 'instructions': '...'}]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['tenant_id', 'service_type']);
        });

        // Checklist Responses
        Schema::create('checklist_responses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('checklist_template_id')->constrained('checklist_templates');
            $table->json('responses'); // [{'id': '1', 'passed': true, 'notes': '...'}]
            $table->timestamps();
            
            $table->unique('work_order_id');
        });

        // Parts Consumption
        Schema::create('parts_consumption', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')->constrained('product_variants');
            $table->decimal('quantity', 15, 4);
            $table->string('source_warehouse_id'); // Technician van or warehouse
            $table->decimal('unit_cost', 15, 4);
            $table->string('currency', 3)->default('MYR');
            $table->timestamps();
            
            $table->index('work_order_id');
        });

        // Customer Signatures
        Schema::create('customer_signatures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained()->cascadeOnDelete();
            $table->text('signature_data'); // Base64 encoded image
            $table->string('signature_hash'); // SHA-256 for integrity
            $table->text('timestamp_signature')->nullable(); // RFC 3161 timestamp (Tier 3)
            $table->dateTime('captured_at');
            $table->foreignUlid('captured_by_technician_id')->constrained('staff');
            $table->json('gps_location')->nullable(); // {lat, lng, accuracy}
            $table->timestamps();
            
            $table->unique('work_order_id');
        });

        // Work Order Photos
        Schema::create('work_order_photos', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'before' | 'after' | 'during'
            $table->foreignUlid('document_id')->constrained('documents');
            $table->integer('sequence')->default(0);
            $table->timestamps();
            
            $table->index(['work_order_id', 'type']);
        });

        // GPS Tracking Log
        Schema::create('gps_tracking_log', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('technician_id')->constrained('staff');
            $table->string('event_type'); // 'job_start' | 'job_end'
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('accuracy_meters')->nullable();
            $table->dateTime('captured_at')->index(); // For GDPR auto-purge
            $table->timestamps();
            
            $table->index(['work_order_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_tracking_log');
        Schema::dropIfExists('work_order_photos');
        Schema::dropIfExists('customer_signatures');
        Schema::dropIfExists('parts_consumption');
        Schema::dropIfExists('checklist_responses');
        Schema::dropIfExists('checklist_templates');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('service_contracts');
    }
};
