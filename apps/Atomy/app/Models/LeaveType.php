<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Hrm\Contracts\LeaveTypeInterface;

class LeaveType extends Model implements LeaveTypeInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'default_days_per_year',
        'max_carry_forward_days',
        'requires_approval',
        'is_unpaid',
        'is_active',
        'accrual_rule',
        'metadata',
    ];

    protected $casts = [
        'default_days_per_year' => 'decimal:2',
        'max_carry_forward_days' => 'decimal:2',
        'requires_approval' => 'boolean',
        'is_unpaid' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    // Interface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDefaultDaysPerYear(): ?float
    {
        return $this->default_days_per_year ? (float) $this->default_days_per_year : null;
    }

    public function getMaxCarryForwardDays(): ?float
    {
        return $this->max_carry_forward_days ? (float) $this->max_carry_forward_days : null;
    }

    public function requiresApproval(): bool
    {
        return $this->requires_approval;
    }

    public function isUnpaid(): bool
    {
        return $this->is_unpaid;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
}
