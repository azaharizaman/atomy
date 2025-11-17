<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Payroll\Contracts\PayslipInterface;
use Nexus\Payroll\ValueObjects\PayslipStatus;

class Payslip extends Model implements PayslipInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'payslip_number',
        'employee_id',
        'period_start',
        'period_end',
        'pay_date',
        'gross_pay',
        'total_earnings',
        'total_deductions',
        'net_pay',
        'employer_contributions',
        'total_cost',
        'status',
        'approved_by',
        'approved_at',
        'paid_at',
        'earnings_breakdown',
        'deductions_breakdown',
        'contributions_breakdown',
        'statutory_metadata',
        'metadata',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'pay_date' => 'date',
        'gross_pay' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'employer_contributions' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'earnings_breakdown' => 'array',
        'deductions_breakdown' => 'array',
        'contributions_breakdown' => 'array',
        'statutory_metadata' => 'array',
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

    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
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

    public function getPayslipNumber(): string
    {
        return $this->payslip_number;
    }

    public function getEmployeeId(): string
    {
        return $this->employee_id;
    }

    public function getPeriodStart(): \DateTimeInterface
    {
        return $this->period_start;
    }

    public function getPeriodEnd(): \DateTimeInterface
    {
        return $this->period_end;
    }

    public function getPayDate(): \DateTimeInterface
    {
        return $this->pay_date;
    }

    public function getGrossPay(): float
    {
        return (float) $this->gross_pay;
    }

    public function getTotalEarnings(): float
    {
        return (float) $this->total_earnings;
    }

    public function getTotalDeductions(): float
    {
        return (float) $this->total_deductions;
    }

    public function getNetPay(): float
    {
        return (float) $this->net_pay;
    }

    public function getEarningsBreakdown(): array
    {
        return $this->earnings_breakdown ?? [];
    }

    public function getDeductionsBreakdown(): array
    {
        return $this->deductions_breakdown ?? [];
    }

    public function getEmployerContributions(): float
    {
        return (float) $this->employer_contributions;
    }

    public function getStatus(): PayslipStatus
    {
        return PayslipStatus::from($this->status);
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isDraft(): bool
    {
        return $this->getStatus() === PayslipStatus::DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->getStatus() === PayslipStatus::APPROVED;
    }

    public function isPaid(): bool
    {
        return $this->getStatus() === PayslipStatus::PAID;
    }
}
