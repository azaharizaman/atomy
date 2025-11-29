<?php

declare(strict_types=1);

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Backoffice\Contracts\StaffInterface;

class Staff extends Model implements StaffInterface
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'backoffice_staff';

    protected $fillable = [
        'tenant_id',
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

    // Relationships

    public function assignments(): HasMany
    {
        return $this->hasMany(StaffAssignment::class);
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(Supervisor::class, 'staff_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Supervisor::class, 'supervisor_id');
    }

    // Interface Implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmployeeId(): string
    {
        return $this->employee_id;
    }

    public function getStaffCode(): ?string
    {
        return $this->staff_code;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getMiddleName(): ?string
    {
        return $this->middle_name;
    }

    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . ($this->middle_name ? $this->middle_name . ' ' : '') . $this->last_name);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function getEmergencyContact(): ?string
    {
        return $this->emergency_contact;
    }

    public function getEmergencyPhone(): ?string
    {
        return $this->emergency_phone;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getHireDate(): \DateTimeInterface
    {
        return $this->hire_date;
    }

    public function getTerminationDate(): ?\DateTimeInterface
    {
        return $this->termination_date;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function getSalaryBand(): ?string
    {
        return $this->salary_band;
    }

    public function getProbationEndDate(): ?\DateTimeInterface
    {
        return $this->probation_end_date;
    }

    public function getConfirmationDate(): ?\DateTimeInterface
    {
        return $this->confirmation_date;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photo_url;
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

    public function isTerminated(): bool
    {
        return $this->status === 'terminated' || ($this->termination_date && $this->termination_date <= now());
    }
}
