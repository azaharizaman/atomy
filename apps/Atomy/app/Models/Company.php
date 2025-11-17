<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Backoffice\Contracts\CompanyInterface;

/**
 * Eloquent model implementing CompanyInterface.
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string|null $registration_number
 * @property string|null $registration_date
 * @property string|null $jurisdiction
 * @property string $status
 * @property string|null $parent_company_id
 * @property int|null $financial_year_start_month
 * @property string|null $industry
 * @property string|null $size
 * @property string|null $tax_id
 * @property array $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Company extends Model implements CompanyInterface
{
    use HasUlids;

    protected $fillable = [
        'code',
        'name',
        'registration_number',
        'registration_date',
        'jurisdiction',
        'status',
        'parent_company_id',
        'financial_year_start_month',
        'industry',
        'size',
        'tax_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'registration_date' => 'date',
        'financial_year_start_month' => 'integer',
    ];

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
