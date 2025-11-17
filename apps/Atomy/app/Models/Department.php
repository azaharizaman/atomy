<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Backoffice\Contracts\DepartmentInterface;

class Department extends Model implements DepartmentInterface
{
    use HasUlids;

    protected $fillable = [
        'company_id', 'code', 'name', 'type', 'status', 'parent_department_id',
        'manager_staff_id', 'cost_center', 'budget_amount', 'description', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'budget_amount' => 'decimal:2',
    ];

    public function getId(): string { return $this->id; }
    public function getCompanyId(): string { return $this->company_id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getParentDepartmentId(): ?string { return $this->parent_department_id; }
    public function getManagerStaffId(): ?string { return $this->manager_staff_id; }
    public function getCostCenter(): ?string { return $this->cost_center; }
    public function getBudgetAmount(): ?float { return $this->budget_amount ? (float) $this->budget_amount : null; }
    public function getDescription(): ?string { return $this->description; }
    public function getMetadata(): array { return $this->metadata ?? []; }
    public function getCreatedAt(): \DateTimeInterface { return $this->created_at; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updated_at; }
    public function isActive(): bool { return $this->status === 'active'; }
}
