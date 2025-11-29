<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Backoffice\Contracts\TransferInterface;

class Transfer extends Model implements TransferInterface
{
    use HasFactory, HasUlids;

    protected $table = 'transfers';

    protected $fillable = [
        'staff_id',
        'from_department_id',
        'to_department_id',
        'from_office_id',
        'to_office_id',
        'effective_date',
        'type',
        'reason',
        'status',
        'requested_by',
        'requested_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
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

    // Interface Implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getStaffId(): string
    {
        return $this->staff_id;
    }

    public function getFromDepartmentId(): ?string
    {
        return $this->from_department_id;
    }

    public function getToDepartmentId(): ?string
    {
        return $this->to_department_id;
    }

    public function getFromOfficeId(): ?string
    {
        return $this->from_office_id;
    }

    public function getToOfficeId(): ?string
    {
        return $this->to_office_id;
    }

    public function getTransferType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEffectiveDate(): \DateTimeInterface
    {
        return $this->effective_date;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getRequestedBy(): string
    {
        return $this->requested_by;
    }

    public function getRequestedAt(): \DateTimeInterface
    {
        return $this->requested_at;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approved_by;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approved_at;
    }

    public function getRejectedBy(): ?string
    {
        return $this->rejected_by;
    }

    public function getRejectedAt(): ?\DateTimeInterface
    {
        return $this->rejected_at;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejection_reason;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completed_at;
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
