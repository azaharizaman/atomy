<?php

declare(strict_types=1);

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Backoffice\Contracts\DepartmentInterface;

class Department extends Model implements DepartmentInterface
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'backoffice_departments';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'parent_department_id',
        'manager_staff_id',
        'code',
        'name',
        'type',
        'status',
        'cost_center',
        'budget_amount',
        'description',
        'metadata',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_department_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_staff_id');
    }

    // Interface Implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getCompanyId(): string
    {
        return $this->company_id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getParentDepartmentId(): ?string
    {
        return $this->parent_department_id;
    }

    public function getManagerStaffId(): ?string
    {
        return $this->manager_staff_id;
    }

    public function getCostCenter(): ?string
    {
        return $this->cost_center;
    }

    public function getBudgetAmount(): ?float
    {
        return $this->budget_amount ? (float) $this->budget_amount : null;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
