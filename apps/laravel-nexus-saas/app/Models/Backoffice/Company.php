<?php

declare(strict_types=1);

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Backoffice\Contracts\CompanyInterface;

class Company extends Model implements CompanyInterface
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'backoffice_companies';

    protected $fillable = [
        'tenant_id',
        'parent_company_id',
        'code',
        'name',
        'registration_number',
        'registration_date',
        'jurisdiction',
        'status',
        'financial_year_start_month',
        'industry',
        'size',
        'tax_id',
        'metadata',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'metadata' => 'array',
        'financial_year_start_month' => 'integer',
    ];

    // Relationships

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    // Interface Implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registration_number;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registration_date;
    }

    public function getJurisdiction(): ?string
    {
        return $this->jurisdiction;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getParentCompanyId(): ?string
    {
        return $this->parent_company_id;
    }

    public function getFinancialYearStartMonth(): ?int
    {
        return $this->financial_year_start_month;
    }

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function getTaxId(): ?string
    {
        return $this->tax_id;
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
}
