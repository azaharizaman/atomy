<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Payroll\Contracts\ComponentInterface;
use Nexus\Payroll\ValueObjects\ComponentType;
use Nexus\Payroll\ValueObjects\CalculationMethod;

class PayrollComponent extends Model implements ComponentInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'type',
        'calculation_method',
        'fixed_amount',
        'percentage_of',
        'percentage_value',
        'reference_component_id',
        'formula',
        'is_statutory',
        'is_taxable',
        'is_active',
        'display_order',
        'metadata',
    ];

    protected $casts = [
        'fixed_amount' => 'decimal:2',
        'percentage_value' => 'decimal:2',
        'is_statutory' => 'boolean',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referenceComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'reference_component_id');
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeeComponent::class, 'component_id');
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): ComponentType
    {
        return ComponentType::from($this->type);
    }

    public function getCalculationMethod(): CalculationMethod
    {
        return CalculationMethod::from($this->calculation_method);
    }

    public function getFixedAmount(): ?float
    {
        return $this->fixed_amount ? (float) $this->fixed_amount : null;
    }

    public function getPercentageOf(): ?string
    {
        return $this->percentage_of;
    }

    public function getPercentageValue(): ?float
    {
        return $this->percentage_value ? (float) $this->percentage_value : null;
    }

    public function getReferenceComponentId(): ?string
    {
        return $this->reference_component_id;
    }

    public function getFormula(): ?string
    {
        return $this->formula;
    }

    public function isStatutory(): bool
    {
        return $this->is_statutory;
    }

    public function isTaxable(): bool
    {
        return $this->is_taxable;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getDisplayOrder(): int
    {
        return $this->display_order;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
}
