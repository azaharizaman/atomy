# Integration Guide: Backoffice

This guide provides complete implementation examples for integrating the Nexus\Backoffice package into Laravel and Symfony applications.

## Table of Contents

- [Laravel Integration](#laravel-integration)
  - [Database Migrations](#database-migrations)
  - [Eloquent Models](#eloquent-models)
  - [Repository Implementations](#repository-implementations)
  - [Service Provider Configuration](#service-provider-configuration)
  - [Controller Examples](#controller-examples)
- [Symfony Integration](#symfony-integration)
  - [Doctrine Entities](#doctrine-entities)
  - [Doctrine Repositories](#doctrine-repositories)
  - [Service Configuration](#service-configuration)
- [Testing Examples](#testing-examples)
- [Performance Optimization](#performance-optimization)

---

## Laravel Integration

### Database Migrations

#### Companies Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('registration_number', 100)->nullable()->unique();
            $table->date('registration_date')->nullable();
            $table->string('jurisdiction', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->ulid('parent_company_id')->nullable();
            $table->tinyInteger('financial_year_start_month')->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('size', 50)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('parent_company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();

            $table->index('status');
            $table->index('parent_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
```

#### Offices Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('company_id');
            $table->string('code', 50);
            $table->string('name');
            $table->string('type', 20);
            $table->string('status', 20)->default('active');
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->nullable(); // ISO 3166-1 alpha-2
            $table->string('timezone', 50)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('fax', 20)->nullable();
            $table->integer('staff_capacity')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->unique(['company_id', 'code']);
            $table->index('company_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
```

#### Departments Table (with Nested Set)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('company_id');
            $table->ulid('parent_department_id')->nullable();
            $table->string('code', 50);
            $table->string('name');
            $table->string('type', 20);
            $table->string('status', 20)->default('active');
            $table->ulid('head_staff_id')->nullable();
            $table->string('cost_center', 50)->nullable();
            $table->decimal('budget_amount', 15, 2)->nullable();
            $table->text('description')->nullable();
            
            // Nested set columns for hierarchical queries
            $table->integer('lft')->default(0);
            $table->integer('rgt')->default(0);
            $table->integer('depth')->default(0);
            
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign('parent_department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->index('company_id');
            $table->index('parent_department_id');
            $table->index('status');
            $table->index(['lft', 'rgt']); // Critical for nested set queries
            $table->index('lft');
            $table->index('rgt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
```

#### Staff Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('employee_id', 50)->unique();
            $table->string('staff_code', 50)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('emergency_contact', 150)->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->string('type', 20);
            $table->string('status', 20)->default('active');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('position', 150)->nullable();
            $table->string('grade', 50)->nullable();
            $table->string('salary_band', 50)->nullable();
            $table->date('probation_end_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->string('photo_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('email');
            $table->index('type');
            $table->index('status');
            $table->index('hire_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
```

#### Staff Assignments Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id');
            $table->ulid('department_id');
            $table->ulid('office_id')->nullable();
            $table->string('role', 100);
            $table->boolean('is_primary')->default(false);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->cascadeOnDelete();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->cascadeOnDelete();

            $table->foreign('office_id')
                ->references('id')
                ->on('offices')
                ->nullOnDelete();

            $table->index(['staff_id', 'is_primary']);
            $table->index('department_id');
            $table->index('office_id');
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_assignments');
    }
};
```

#### Staff Supervisors Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_supervisors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id');
            $table->ulid('supervisor_id');
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->cascadeOnDelete();

            $table->foreign('supervisor_id')
                ->references('id')
                ->on('staff')
                ->cascadeOnDelete();

            $table->index(['staff_id', 'effective_date']);
            $table->index('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_supervisors');
    }
};
```

#### Units Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('company_id');
            $table->string('code', 50);
            $table->string('name');
            $table->string('type', 20);
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->ulid('leader_staff_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign('leader_staff_id')
                ->references('id')
                ->on('staff')
                ->nullOnDelete();

            $table->unique(['company_id', 'code']);
            $table->index('company_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
```

#### Unit Members Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('unit_id');
            $table->ulid('staff_id');
            $table->string('role', 50); // leader, member, secretary, advisor
            $table->date('joined_date');
            $table->date('left_date')->nullable();
            $table->timestamps();

            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->cascadeOnDelete();

            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->cascadeOnDelete();

            $table->index(['unit_id', 'staff_id']);
            $table->index('staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_members');
    }
};
```

#### Transfers Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id');
            $table->ulid('from_department_id');
            $table->ulid('to_department_id');
            $table->ulid('from_office_id')->nullable();
            $table->ulid('to_office_id')->nullable();
            $table->string('type', 20);
            $table->string('status', 20)->default('pending');
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->ulid('requested_by');
            $table->ulid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_comment')->nullable();
            $table->ulid('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->cascadeOnDelete();

            $table->foreign('from_department_id')
                ->references('id')
                ->on('departments')
                ->restrictOnDelete();

            $table->foreign('to_department_id')
                ->references('id')
                ->on('departments')
                ->restrictOnDelete();

            $table->foreign('from_office_id')
                ->references('id')
                ->on('offices')
                ->nullOnDelete();

            $table->foreign('to_office_id')
                ->references('id')
                ->on('offices')
                ->nullOnDelete();

            $table->foreign('requested_by')
                ->references('id')
                ->on('staff')
                ->restrictOnDelete();

            $table->index(['staff_id', 'status']);
            $table->index('status');
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
```

---

### Eloquent Models

#### Company Model

```php
<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Company extends Model
{
    use HasUlids;

    protected $table = 'companies';

    protected $fillable = [
        'code',
        'name',
        'registration_number',
        'registration_date',
        'jurisdiction',
        'status',
        'parent_company_id',
        'financial_year_start_month',
        'industry',
        'size',
        'tax_id',
        'metadata',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'metadata' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function subsidiaries()
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    public function offices()
    {
        return $this->hasMany(Office::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
```

#### Office Model

```php
<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Office extends Model
{
    use HasUlids;

    protected $table = 'offices';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'status',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'timezone',
        'phone',
        'email',
        'fax',
        'staff_capacity',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
```

#### Department Model (with Nested Set)

```php
<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Kalnoy\Nestedset\NodeTrait;

class Department extends Model
{
    use HasUlids, NodeTrait;

    protected $table = 'departments';

    protected $fillable = [
        'company_id',
        'parent_department_id',
        'code',
        'name',
        'type',
        'status',
        'head_staff_id',
        'cost_center',
        'budget_amount',
        'description',
        'metadata',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_department_id');
    }

    public function head()
    {
        return $this->belongsTo(Staff::class, 'head_staff_id');
    }

    public function assignments()
    {
        return $this->hasMany(StaffAssignment::class);
    }

    // Nested set helper methods
    public function getDescendants()
    {
        return static::where('lft', '>', $this->lft)
            ->where('rgt', '<', $this->rgt)
            ->where('company_id', $this->company_id)
            ->orderBy('lft')
            ->get();
    }

    public function getAncestors()
    {
        return static::where('lft', '<', $this->lft)
            ->where('rgt', '>', $this->rgt)
            ->where('company_id', $this->company_id)
            ->orderBy('lft')
            ->get();
    }
}
```

#### Staff Model

```php
<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Staff extends Model
{
    use HasUlids;

    protected $table = 'staff';

    protected $fillable = [
        'employee_id',
        'staff_code',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'mobile',
        'emergency_contact',
        'emergency_phone',
        'type',
        'status',
        'hire_date',
        'termination_date',
        'position',
        'grade',
        'salary_band',
        'probation_end_date',
        'confirmation_date',
        'photo_url',
        'metadata',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'probation_end_date' => 'date',
        'confirmation_date' => 'date',
        'metadata' => 'array',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);

        return implode(' ', $parts);
    }

    public function assignments()
    {
        return $this->hasMany(StaffAssignment::class);
    }

    public function primaryAssignment()
    {
        return $this->hasOne(StaffAssignment::class)
            ->where('is_primary', true)
            ->whereNull('end_date')
            ->latest('effective_date');
    }

    public function supervisorRelations()
    {
        return $this->hasMany(StaffSupervisor::class, 'staff_id');
    }

    public function subordinateRelations()
    {
        return $this->hasMany(StaffSupervisor::class, 'supervisor_id');
    }

    public function currentSupervisor()
    {
        return $this->hasOneThrough(
            Staff::class,
            StaffSupervisor::class,
            'staff_id',
            'id',
            'id',
            'supervisor_id'
        )->whereNull('staff_supervisors.end_date')
            ->latest('staff_supervisors.effective_date');
    }

    public function unitMemberships()
    {
        return $this->hasMany(UnitMember::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }
}
```

#### Unit Model

```php
<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Unit extends Model
{
    use HasUlids;

    protected $table = 'units';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'status',
        'description',
        'start_date',
        'end_date',
        'leader_staff_id',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function leader()
    {
        return $this->belongsTo(Staff::class, 'leader_staff_id');
    }

    public function members()
    {
        return $this->hasMany(UnitMember::class);
    }

    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'unit_members')
            ->withPivot('role', 'joined_date', 'left_date')
            ->withTimestamps();
    }
}
```

#### Transfer Model

```php
<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Transfer extends Model
{
    use HasUlids;

    protected $table = 'transfers';

    protected $fillable = [
        'staff_id',
        'from_department_id',
        'to_department_id',
        'from_office_id',
        'to_office_id',
        'type',
        'status',
        'effective_date',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_comment',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'completed_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromOffice()
    {
        return $this->belongsTo(Office::class, 'from_office_id');
    }

    public function toOffice()
    {
        return $this->belongsTo(Office::class, 'to_office_id');
    }

    public function requester()
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }
}
```

---

### Repository Implementations

#### DbCompanyRepository (Example)

```php
<?php

namespace App\Repositories\Backoffice;

use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\CompanyInterface;
use App\Models\Backoffice\Company as CompanyModel;
use App\ValueObjects\Backoffice\Company;

final readonly class DbCompanyRepository implements CompanyRepositoryInterface
{
    public function save(array $data): CompanyInterface
    {
        $model = CompanyModel::create($data);
        return $this->modelToInterface($model);
    }

    public function update(string $id, array $data): CompanyInterface
    {
        $model = CompanyModel::findOrFail($id);
        $model->update($data);
        return $this->modelToInterface($model->fresh());
    }

    public function delete(string $id): bool
    {
        return CompanyModel::where('id', $id)->delete() > 0;
    }

    public function findById(string $id): ?CompanyInterface
    {
        $model = CompanyModel::find($id);
        return $model ? $this->modelToInterface($model) : null;
    }

    public function findByCode(string $code): ?CompanyInterface
    {
        $model = CompanyModel::where('code', $code)->first();
        return $model ? $this->modelToInterface($model) : null;
    }

    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $query = CompanyModel::where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function registrationNumberExists(string $number, ?string $excludeId = null): bool
    {
        $query = CompanyModel::where('registration_number', $number);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function hasCircularReference(string $companyId, string $parentId): bool
    {
        if ($companyId === 'new') {
            return false;
        }

        $current = $parentId;
        $visited = [];

        while ($current) {
            if ($current === $companyId) {
                return true;
            }

            if (isset($visited[$current])) {
                break;
            }

            $visited[$current] = true;
            $parent = CompanyModel::find($current);
            $current = $parent?->parent_company_id;
        }

        return false;
    }

    public function getSubsidiaries(string $companyId): array
    {
        return CompanyModel::where('parent_company_id', $companyId)
            ->get()
            ->map(fn($m) => $this->modelToInterface($m))
            ->all();
    }

    public function getParentChain(string $companyId): array
    {
        $chain = [];
        $current = CompanyModel::find($companyId);

        while ($current && $current->parent_company_id) {
            $parent = CompanyModel::find($current->parent_company_id);
            if ($parent) {
                $chain[] = $this->modelToInterface($parent);
                $current = $parent;
            } else {
                break;
            }
        }

        return $chain;
    }

    public function findAll(): array
    {
        return CompanyModel::all()
            ->map(fn($m) => $this->modelToInterface($m))
            ->all();
    }

    private function modelToInterface(CompanyModel $model): CompanyInterface
    {
        return new Company(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            registrationNumber: $model->registration_number,
            registrationDate: $model->registration_date,
            jurisdiction: $model->jurisdiction,
            status: $model->status,
            parentCompanyId: $model->parent_company_id,
            financialYearStartMonth: $model->financial_year_start_month,
            industry: $model->industry,
            size: $model->size,
            taxId: $model->tax_id,
            metadata: $model->metadata ?? [],
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: new \DateTimeImmutable($model->updated_at),
        );
    }
}
```

---

### Service Provider Configuration

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\TransferManagerInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Contracts\UnitRepositoryInterface;
use Nexus\Backoffice\Contracts\TransferRepositoryInterface;
use Nexus\Backoffice\Services\BackofficeManager;
use Nexus\Backoffice\Services\TransferManager;
use App\Repositories\Backoffice\DbCompanyRepository;
use App\Repositories\Backoffice\DbOfficeRepository;
use App\Repositories\Backoffice\DbDepartmentRepository;
use App\Repositories\Backoffice\DbStaffRepository;
use App\Repositories\Backoffice\DbUnitRepository;
use App\Repositories\Backoffice\DbTransferRepository;

final class BackofficeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(CompanyRepositoryInterface::class, DbCompanyRepository::class);
        $this->app->singleton(OfficeRepositoryInterface::class, DbOfficeRepository::class);
        $this->app->singleton(DepartmentRepositoryInterface::class, DbDepartmentRepository::class);
        $this->app->singleton(StaffRepositoryInterface::class, DbStaffRepository::class);
        $this->app->singleton(UnitRepositoryInterface::class, DbUnitRepository::class);
        $this->app->singleton(TransferRepositoryInterface::class, DbTransferRepository::class);

        // Bind services
        $this->app->singleton(BackofficeManagerInterface::class, BackofficeManager::class);
        $this->app->singleton(TransferManagerInterface::class, TransferManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations/backoffice');
        }
    }
}
```

---

### Controller Examples

#### CompanyController

```php
<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Exceptions\CompanyNotFoundException;
use Nexus\Backoffice\Exceptions\DuplicateCodeException;

final class CompanyController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $backofficeManager
    ) {}

    public function index(): JsonResponse
    {
        // Implement with repository for listing
        return response()->json(['message' => 'List companies']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'registration_date' => 'nullable|date',
            'jurisdiction' => 'nullable|string|max:100',
            'parent_company_id' => 'nullable|ulid|exists:companies,id',
            'financial_year_start_month' => 'nullable|integer|min:1|max:12',
            'industry' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
        ]);

        try {
            $company = $this->backofficeManager->createCompany($validated);

            return response()->json([
                'message' => 'Company created successfully',
                'data' => [
                    'id' => $company->getId(),
                    'code' => $company->getCode(),
                    'name' => $company->getName(),
                ],
            ], 201);
        } catch (DuplicateCodeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $company = $this->backofficeManager->getCompany($id);

            if (!$company) {
                throw new CompanyNotFoundException($id);
            }

            return response()->json([
                'data' => [
                    'id' => $company->getId(),
                    'code' => $company->getCode(),
                    'name' => $company->getName(),
                    'status' => $company->getStatus(),
                    'registration_number' => $company->getRegistrationNumber(),
                    'registration_date' => $company->getRegistrationDate()?->format('Y-m-d'),
                    'jurisdiction' => $company->getJurisdiction(),
                    'parent_company_id' => $company->getParentCompanyId(),
                    'industry' => $company->getIndustry(),
                    'size' => $company->getSize(),
                    'tax_id' => $company->getTaxId(),
                    'is_active' => $company->isActive(),
                ],
            ]);
        } catch (CompanyNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|in:active,inactive,suspended,dissolved',
            'parent_company_id' => 'nullable|ulid|exists:companies,id',
        ]);

        try {
            $company = $this->backofficeManager->updateCompany($id, $validated);

            return response()->json([
                'message' => 'Company updated successfully',
                'data' => [
                    'id' => $company->getId(),
                    'code' => $company->getCode(),
                    'name' => $company->getName(),
                ],
            ]);
        } catch (CompanyNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DuplicateCodeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->backofficeManager->deleteCompany($id);

            return response()->json([
                'message' => 'Company deleted successfully',
            ]);
        } catch (CompanyNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
```

---

## Symfony Integration

### Doctrine Entities

#### Company Entity

```php
<?php

namespace App\Entity\Backoffice;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'companies')]
class Company
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true, unique: true)]
    private ?string $registrationNumber = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $registrationDate = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $jurisdiction = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'subsidiaries')]
    #[ORM\JoinColumn(name: 'parent_company_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Company $parentCompany = null;

    #[ORM\OneToMany(mappedBy: 'parentCompany', targetEntity: Company::class)]
    private Collection $subsidiaries;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $financialYearStartMonth = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $industry = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $size = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $taxId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $metadata = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = new Ulid();
        $this->subsidiaries = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters...
}
```

---

### Doctrine Repositories

```php
<?php

namespace App\Repository\Backoffice;

use App\Entity\Backoffice\Company as CompanyEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\CompanyInterface;
use App\ValueObjects\Backoffice\Company;

class DoctrineCompanyRepository extends ServiceEntityRepository implements CompanyRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyEntity::class);
    }

    public function save(array $data): CompanyInterface
    {
        $entity = new CompanyEntity();
        // Map data to entity...
        
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $this->entityToInterface($entity);
    }

    // Implement other methods...

    private function entityToInterface(CompanyEntity $entity): CompanyInterface
    {
        return new Company(
            id: (string) $entity->getId(),
            code: $entity->getCode(),
            name: $entity->getName(),
            // Map other properties...
        );
    }
}
```

---

### Service Configuration

```yaml
# config/services.yaml
services:
    # Repositories
    Nexus\Backoffice\Contracts\CompanyRepositoryInterface:
        class: App\Repository\Backoffice\DoctrineCompanyRepository
        arguments: ['@doctrine']

    Nexus\Backoffice\Contracts\OfficeRepositoryInterface:
        class: App\Repository\Backoffice\DoctrineOfficeRepository
        arguments: ['@doctrine']

    # Services
    Nexus\Backoffice\Contracts\BackofficeManagerInterface:
        class: Nexus\Backoffice\Services\BackofficeManager
        arguments:
            - '@Nexus\Backoffice\Contracts\CompanyRepositoryInterface'
            - '@Nexus\Backoffice\Contracts\OfficeRepositoryInterface'
            - '@Nexus\Backoffice\Contracts\DepartmentRepositoryInterface'
            - '@Nexus\Backoffice\Contracts\StaffRepositoryInterface'
            - '@Nexus\Backoffice\Contracts\UnitRepositoryInterface'
```

---

## Testing Examples

### Unit Test Example

```php
<?php

namespace Tests\Unit\Backoffice;

use Tests\TestCase;
use Nexus\Backoffice\Services\BackofficeManager;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Exceptions\DuplicateCodeException;
use Mockery;

class BackofficeManagerTest extends TestCase
{
    public function test_create_company_throws_exception_for_duplicate_code(): void
    {
        $companyRepo = Mockery::mock(CompanyRepositoryInterface::class);
        $companyRepo->shouldReceive('codeExists')
            ->with('ABC')
            ->once()
            ->andReturn(true);

        $manager = new BackofficeManager(
            $companyRepo,
            Mockery::mock(OfficeRepositoryInterface::class),
            Mockery::mock(DepartmentRepositoryInterface::class),
            Mockery::mock(StaffRepositoryInterface::class),
            Mockery::mock(UnitRepositoryInterface::class),
        );

        $this->expectException(DuplicateCodeException::class);

        $manager->createCompany([
            'code' => 'ABC',
            'name' => 'Test Company',
        ]);
    }
}
```

### Feature Test Example

```php
<?php

namespace Tests\Feature\Backoffice;

use Tests\TestCase;
use App\Models\Backoffice\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_company(): void
    {
        $response = $this->postJson('/api/backoffice/companies', [
            'code' => 'TEST',
            'name' => 'Test Company Ltd',
            'registration_number' => '202301234567',
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Company created successfully',
            ]);

        $this->assertDatabaseHas('companies', [
            'code' => 'TEST',
            'name' => 'Test Company Ltd',
        ]);
    }

    public function test_can_create_subsidiary_company(): void
    {
        $parent = Company::factory()->create([
            'code' => 'PARENT',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/backoffice/companies', [
            'code' => 'CHILD',
            'name' => 'Child Company',
            'parent_company_id' => $parent->id,
            'status' => 'active',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('companies', [
            'code' => 'CHILD',
            'parent_company_id' => $parent->id,
        ]);
    }
}
```

---

## Performance Optimization

### Database Indexing

Ensure proper indexes are created for optimal query performance:

```sql
-- Companies
CREATE INDEX idx_companies_status ON companies(status);
CREATE INDEX idx_companies_parent ON companies(parent_company_id);

-- Departments (Nested Set)
CREATE INDEX idx_departments_nested ON departments(lft, rgt);
CREATE INDEX idx_departments_company ON departments(company_id);
CREATE INDEX idx_departments_parent ON departments(parent_department_id);

-- Staff
CREATE INDEX idx_staff_employee_id ON staff(employee_id);
CREATE INDEX idx_staff_email ON staff(email);
CREATE INDEX idx_staff_status ON staff(status);

-- Staff Assignments
CREATE INDEX idx_assignments_staff_primary ON staff_assignments(staff_id, is_primary);
CREATE INDEX idx_assignments_department ON staff_assignments(department_id);

-- Transfers
CREATE INDEX idx_transfers_staff_status ON transfers(staff_id, status);
CREATE INDEX idx_transfers_effective_date ON transfers(effective_date);
```

### Caching Hierarchical Queries

```php
use Illuminate\Support\Facades\Cache;

// Cache department descendants
$descendants = Cache::remember(
    "department:{$departmentId}:descendants",
    now()->addHours(24),
    fn() => $departmentRepo->getDescendants($departmentId)
);

// Invalidate cache on department updates
Cache::forget("department:{$departmentId}:descendants");
```

### Eager Loading

```php
// Avoid N+1 queries
$companies = Company::with(['parent', 'subsidiaries', 'offices'])->get();

$staff = Staff::with([
    'primaryAssignment.department',
    'primaryAssignment.office',
    'currentSupervisor',
])->get();
```

### Nested Set Optimization

Use the `kalnoy/nestedset` package for Laravel:

```bash
composer require kalnoy/nestedset
```

```php
use Kalnoy\Nestedset\NodeTrait;

class Department extends Model
{
    use NodeTrait;

    // Efficient hierarchical queries
    $descendants = $department->descendants()->get();
    $ancestors = $department->ancestors()->get();
    $siblings = $department->siblings()->get();
}
```

---

This integration guide provides complete examples for implementing the Nexus\Backoffice package in Laravel and Symfony applications with production-ready patterns.
