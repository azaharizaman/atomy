<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Hrm\Contracts\DisciplinaryInterface;
use Nexus\Hrm\ValueObjects\DisciplinaryStatus;
use Nexus\Hrm\ValueObjects\DisciplinarySeverity;

class DisciplinaryCase extends Model implements DisciplinaryInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'case_number',
        'incident_date',
        'reported_date',
        'reported_by',
        'category',
        'severity',
        'description',
        'status',
        'investigation_notes',
        'investigated_by',
        'investigation_completed_at',
        'resolution',
        'action_taken',
        'closed_at',
        'closed_by',
        'follow_up_date',
        'metadata',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'reported_date' => 'date',
        'investigation_completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'follow_up_date' => 'date',
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

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reported_by');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'investigated_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'closed_by');
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

    public function getCaseNumber(): string
    {
        return $this->case_number;
    }

    public function getIncidentDate(): \DateTimeInterface
    {
        return $this->incident_date;
    }

    public function getReportedDate(): \DateTimeInterface
    {
        return $this->reported_date;
    }

    public function getReportedBy(): string
    {
        return $this->reported_by;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getSeverity(): DisciplinarySeverity
    {
        return DisciplinarySeverity::from($this->severity);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): DisciplinaryStatus
    {
        return DisciplinaryStatus::from($this->status);
    }

    public function getInvestigationNotes(): ?string
    {
        return $this->investigation_notes;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function getActionTaken(): ?string
    {
        return $this->action_taken;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isOpen(): bool
    {
        return $this->getStatus() === DisciplinaryStatus::REPORTED 
            || $this->getStatus() === DisciplinaryStatus::UNDER_INVESTIGATION;
    }

    public function isClosed(): bool
    {
        return $this->getStatus() === DisciplinaryStatus::CLOSED;
    }

    public function isUnderInvestigation(): bool
    {
        return $this->getStatus() === DisciplinaryStatus::UNDER_INVESTIGATION;
    }
}
