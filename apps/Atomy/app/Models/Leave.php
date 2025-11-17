<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Hrm\Contracts\LeaveInterface;
use Nexus\Hrm\ValueObjects\LeaveStatus;

class Leave extends Model implements LeaveInterface
{
    use HasUlids, SoftDeletes;

    protected $table = 'leaves';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'cancelled_at',
        'cancellation_reason',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
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

    public function getStartDate(): \DateTimeInterface
    {
        return $this->start_date;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->end_date;
    }

    public function getTotalDays(): float
    {
        return (float) $this->total_days;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getStatus(): LeaveStatus
    {
        return LeaveStatus::from($this->status);
    }

    public function getSubmittedAt(): \DateTimeInterface
    {
        return $this->submitted_at;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approved_by;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approved_at;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejection_reason;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isPending(): bool
    {
        return $this->getStatus() === LeaveStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->getStatus() === LeaveStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->getStatus() === LeaveStatus::REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->getStatus() === LeaveStatus::CANCELLED;
    }
}
