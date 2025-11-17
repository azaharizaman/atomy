<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Companies table
        Schema::create('companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('registration_number')->nullable()->unique();
            $table->date('registration_date')->nullable();
            $table->string('jurisdiction')->nullable();
            $table->string('status')->default('active')->index();
            $table->ulid('parent_company_id')->nullable()->index();
            $table->unsignedTinyInteger('financial_year_start_month')->nullable();
            $table->string('industry')->nullable();
            $table->string('size')->nullable();
            $table->string('tax_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('parent_company_id')->references('id')->on('companies')->onDelete('set null');
        });

        // Offices table
        Schema::create('offices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('company_id')->index();
            $table->string('code');
            $table->string('name');
            $table->string('type')->index();
            $table->string('status')->default('active')->index();
            $table->ulid('parent_office_id')->nullable()->index();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('postal_code');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('fax')->nullable();
            $table->string('timezone')->nullable();
            $table->text('operating_hours')->nullable();
            $table->unsignedInteger('staff_capacity')->nullable();
            $table->decimal('floor_area', 10, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_office_id')->references('id')->on('offices')->onDelete('set null');
        });

        // Departments table
        Schema::create('departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('company_id')->index();
            $table->string('code');
            $table->string('name');
            $table->string('type')->index();
            $table->string('status')->default('active')->index();
            $table->ulid('parent_department_id')->nullable()->index();
            $table->ulid('manager_staff_id')->nullable()->index();
            $table->string('cost_center')->nullable();
            $table->decimal('budget_amount', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code', 'parent_department_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_department_id')->references('id')->on('departments')->onDelete('set null');
        });

        // Staff table
        Schema::create('staff', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('employee_id')->unique();
            $table->string('staff_code')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('type')->index();
            $table->string('status')->default('active')->index();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('position')->nullable();
            $table->string('grade')->nullable();
            $table->string('salary_band')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->string('photo_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['email', 'status']);
        });

        // Staff department assignments table
        Schema::create('staff_department_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id')->index();
            $table->ulid('department_id')->index();
            $table->string('role');
            $table->boolean('is_primary')->default(false)->index();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });

        // Staff office assignments table
        Schema::create('staff_office_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id')->index();
            $table->ulid('office_id')->index();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('office_id')->references('id')->on('offices')->onDelete('cascade');
        });

        // Staff supervisor relationships table
        Schema::create('staff_supervisors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id')->index();
            $table->ulid('supervisor_id')->index();
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_dotted_line')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('supervisor_id')->references('id')->on('staff')->onDelete('cascade');
        });

        // Backoffice units table
        Schema::create('backoffice_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('company_id')->index();
            $table->string('code');
            $table->string('name');
            $table->string('type')->index();
            $table->string('status')->default('active')->index();
            $table->ulid('leader_staff_id')->nullable()->index();
            $table->ulid('deputy_leader_staff_id')->nullable()->index();
            $table->text('purpose')->nullable();
            $table->text('objectives')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('leader_staff_id')->references('id')->on('staff')->onDelete('set null');
            $table->foreign('deputy_leader_staff_id')->references('id')->on('staff')->onDelete('set null');
        });

        // Unit members table
        Schema::create('unit_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('unit_id')->index();
            $table->ulid('staff_id')->index();
            $table->string('role')->default('member');
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'staff_id']);
            $table->foreign('unit_id')->references('id')->on('backoffice_units')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });

        // Staff transfers table
        Schema::create('staff_transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id')->index();
            $table->ulid('from_department_id')->nullable()->index();
            $table->ulid('to_department_id')->nullable()->index();
            $table->ulid('from_office_id')->nullable()->index();
            $table->ulid('to_office_id')->nullable()->index();
            $table->string('transfer_type');
            $table->string('status')->default('pending')->index();
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->string('requested_by');
            $table->timestamp('requested_at');
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('from_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('to_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('from_office_id')->references('id')->on('offices')->onDelete('set null');
            $table->foreign('to_office_id')->references('id')->on('offices')->onDelete('set null');
        });

        // Add foreign key for department manager after staff table is created
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_staff_id')->references('id')->on('staff')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_transfers');
        Schema::dropIfExists('unit_members');
        Schema::dropIfExists('backoffice_units');
        Schema::dropIfExists('staff_supervisors');
        Schema::dropIfExists('staff_office_assignments');
        Schema::dropIfExists('staff_department_assignments');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('offices');
        Schema::dropIfExists('companies');
    }
};
