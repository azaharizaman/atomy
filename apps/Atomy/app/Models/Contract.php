<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Hrm\Contracts\ContractInterface;
use Nexus\Hrm\ValueObjects\ContractType;
use Nexus\Hrm\ValueObjects\PayFrequency;

class Contract extends Model implements ContractInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'contract_type',
        'start_date',
        'end_date',
        'basic_salary',
        'currency',
        'pay_frequency',
        'probation_period_months',
        'notice_period_days',
        'working_hours_per_week',
        'terms',
        'status',
        'signed_at',
        'approved_by',
        'approved_at',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'basic_salary' => 'decimal:2',
        'working_hours_per_week' => 'decimal:2',
        'signed_at' => 'datetime',
        'approved_at' => 'datetime',
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

    public function getContractType(): ContractType
    {
        return ContractType::from($this->contract_type);
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->start_date;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function getBasicSalary(): float
    {
        return (float) $this->basic_salary;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPayFrequency(): PayFrequency
    {
        return PayFrequency::from($this->pay_frequency);
    }

    public function getProbationPeriodMonths(): ?int
    {
        return $this->probation_period_months;
    }

    public function getNoticePeriodDays(): ?int
    {
        return $this->notice_period_days;
    }

    public function getWorkingHoursPerWeek(): ?float
    {
        return $this->working_hours_per_week ? (float) $this->working_hours_per_week : null;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        if (!$this->end_date) {
            return false;
        }
        return $this->end_date < now();
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }
}
