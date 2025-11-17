<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Hrm\Contracts\TrainingEnrollmentInterface;
use Nexus\Hrm\ValueObjects\EnrollmentStatus;

class TrainingEnrollment extends Model implements TrainingEnrollmentInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'training_id',
        'employee_id',
        'enrolled_at',
        'status',
        'completed_at',
        'attendance_percentage',
        'score',
        'passing_score',
        'is_passed',
        'certificate_issued',
        'certificate_issued_at',
        'feedback',
        'metadata',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'attendance_percentage' => 'decimal:2',
        'score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'is_passed' => 'boolean',
        'certificate_issued' => 'boolean',
        'certificate_issued_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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

    public function getTrainingId(): string
    {
        return $this->training_id;
    }

    public function getEmployeeId(): string
    {
        return $this->employee_id;
    }

    public function getEnrolledAt(): \DateTimeInterface
    {
        return $this->enrolled_at;
    }

    public function getStatus(): EnrollmentStatus
    {
        return EnrollmentStatus::from($this->status);
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }

    public function getAttendancePercentage(): ?float
    {
        return $this->attendance_percentage ? (float) $this->attendance_percentage : null;
    }

    public function getScore(): ?float
    {
        return $this->score ? (float) $this->score : null;
    }

    public function isPassed(): ?bool
    {
        return $this->is_passed;
    }

    public function isCertificateIssued(): bool
    {
        return $this->certificate_issued;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isCompleted(): bool
    {
        return $this->getStatus() === EnrollmentStatus::COMPLETED;
    }

    public function isInProgress(): bool
    {
        return $this->getStatus() === EnrollmentStatus::IN_PROGRESS;
    }
}
