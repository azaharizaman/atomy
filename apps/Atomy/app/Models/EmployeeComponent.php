<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Payroll\Contracts\EmployeeComponentInterface;

class EmployeeComponent extends Model implements EmployeeComponentInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'component_id',
        'amount',
        'percentage_value',
        'effective_from',
        'effective_to',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage_value' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
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

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
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

    public function getComponentId(): string
    {
        return $this->component_id;
    }

    public function getAmount(): ?float
    {
        return $this->amount ? (float) $this->amount : null;
    }

    public function getPercentageValue(): ?float
    {
        return $this->percentage_value ? (float) $this->percentage_value : null;
    }

    public function getEffectiveFrom(): \DateTimeInterface
    {
        return $this->effective_from;
    }

    public function getEffectiveTo(): ?\DateTimeInterface
    {
        return $this->effective_to;
    }

    public function isActive(): bool
    {
        $now = now();
        
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->effective_from > $now) {
            return false;
        }
        
        if ($this->effective_to && $this->effective_to < $now) {
            return false;
        }
        
        return true;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
}
