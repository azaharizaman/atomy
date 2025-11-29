<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Nexus\Backoffice\Contracts\UnitInterface;
use Nexus\Backoffice\Contracts\CompanyInterface;
use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\Backoffice\Enums\UnitStatus;
use Nexus\Backoffice\Enums\UnitType;

class Unit extends Model implements UnitInterface
{
    use HasFactory;

    protected $table = 'backoffice_units';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'type',
        'status',
        'leader_staff_id',
        'deputy_leader_staff_id',
        'purpose',
        'objectives',
        'start_date',
        'end_date',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
        'type' => UnitType::class,
        'status' => UnitStatus::class,
    ];

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

    public function getType(): UnitType
    {
        return $this->type;
    }

    public function getStatus(): UnitStatus
    {
        return $this->status;
    }

    public function getLeaderStaffId(): ?string
    {
        return $this->leader_staff_id;
    }

    public function getDeputyLeaderStaffId(): ?string
    {
        return $this->deputy_leader_staff_id;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function getObjectives(): ?string
    {
        return $this->objectives;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
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
        return $this->status->isActive();
    }

    public function isTemporary(): bool
    {
        return $this->type->isTemporaryByNature();
    }

    public function getCompany(): ?CompanyInterface
    {
        return $this->company;
    }

    public function getLeader(): ?StaffInterface
    {
        return $this->leader;
    }

    public function getDeputyLeader(): ?StaffInterface
    {
        return $this->deputyLeader;
    }

    public function getMembers(): array
    {
        return $this->members->all();
    }

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'leader_staff_id');
    }

    public function deputyLeader(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'deputy_leader_staff_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'backoffice_unit_members', 'unit_id', 'staff_id')
            ->withPivot('role')
            ->withTimestamps();
    }
}
