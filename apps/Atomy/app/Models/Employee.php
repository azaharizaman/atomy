<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Hrm\Contracts\EmployeeInterface;
use Nexus\Hrm\ValueObjects\EmployeeStatus;
use Nexus\Hrm\ValueObjects\EmploymentType;

class Employee extends Model implements EmployeeInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'date_of_birth',
        'hire_date',
        'confirmation_date',
        'termination_date',
        'status',
        'manager_id',
        'department_id',
        'office_id',
        'job_title',
        'employment_type',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'confirmation_date' => 'date',
        'termination_date' => 'date',
        'metadata' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
    }

    public function trainingEnrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
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

    public function getEmployeeCode(): string
    {
        return $this->employee_code;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getFullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function getDateOfBirth(): \DateTimeInterface
    {
        return $this->date_of_birth;
    }

    public function getHireDate(): \DateTimeInterface
    {
        return $this->hire_date;
    }

    public function getConfirmationDate(): ?\DateTimeInterface
    {
        return $this->confirmation_date;
    }

    public function getTerminationDate(): ?\DateTimeInterface
    {
        return $this->termination_date;
    }

    public function getStatus(): EmployeeStatus
    {
        return EmployeeStatus::from($this->status);
    }

    public function getManagerId(): ?string
    {
        return $this->manager_id;
    }

    public function getDepartmentId(): ?string
    {
        return $this->department_id;
    }

    public function getOfficeId(): ?string
    {
        return $this->office_id;
    }

    public function getJobTitle(): ?string
    {
        return $this->job_title;
    }

    public function getEmploymentType(): EmploymentType
    {
        return EmploymentType::from($this->employment_type);
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isProbationary(): bool
    {
        return $this->getStatus() === EmployeeStatus::PROBATIONARY;
    }

    public function isConfirmed(): bool
    {
        return $this->getStatus() === EmployeeStatus::CONFIRMED;
    }

    public function isTerminated(): bool
    {
        return $this->getStatus() === EmployeeStatus::TERMINATED;
    }

    public function isActive(): bool
    {
        return !in_array($this->getStatus(), [
            EmployeeStatus::TERMINATED,
            EmployeeStatus::RESIGNED,
            EmployeeStatus::RETIRED,
        ]);
    }
}
