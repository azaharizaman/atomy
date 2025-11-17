<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Hrm\Contracts\LeaveBalanceInterface;

class LeaveBalance extends Model implements LeaveBalanceInterface
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'year',
        'entitled_days',
        'used_days',
        'carried_forward_days',
        'metadata',
    ];

    protected $casts = [
        'entitled_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'carried_forward_days' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
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

    public function getEmployeeId(): string
    {
        return $this->employee_id;
    }

    public function getLeaveTypeId(): string
    {
        return $this->leave_type_id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getEntitledDays(): float
    {
        return (float) $this->entitled_days;
    }

    public function getUsedDays(): float
    {
        return (float) $this->used_days;
    }

    public function getCarriedForwardDays(): float
    {
        return (float) $this->carried_forward_days;
    }

    public function getRemainingDays(): float
    {
        return $this->getEntitledDays() + $this->getCarriedForwardDays() - $this->getUsedDays();
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function hasSufficientBalance(float $days): bool
    {
        return $this->getRemainingDays() >= $days;
    }
}
