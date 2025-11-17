<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Backoffice\Contracts\UnitInterface;

class BackofficeUnit extends Model implements UnitInterface
{
    use HasUlids;

    protected $table = 'backoffice_units';

    protected $fillable = [
        'company_id', 'code', 'name', 'type', 'status', 'leader_staff_id',
        'deputy_leader_staff_id', 'purpose', 'objectives', 'start_date', 'end_date', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getId(): string { return $this->id; }
    public function getCompanyId(): string { return $this->company_id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getLeaderStaffId(): ?string { return $this->leader_staff_id; }
    public function getDeputyLeaderStaffId(): ?string { return $this->deputy_leader_staff_id; }
    public function getPurpose(): ?string { return $this->purpose; }
    public function getObjectives(): ?string { return $this->objectives; }
    public function getStartDate(): ?\DateTimeInterface { return $this->start_date; }
    public function getEndDate(): ?\DateTimeInterface { return $this->end_date; }
    public function getMetadata(): array { return $this->metadata ?? []; }
    public function getCreatedAt(): \DateTimeInterface { return $this->created_at; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updated_at; }
    public function isActive(): bool { return $this->status === 'active'; }
    public function isTemporary(): bool { return $this->start_date && $this->end_date; }
}
