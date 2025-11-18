<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Enums\PeriodStatus;
use Illuminate\Database\Eloquent\Model;
use Nexus\Period\Contracts\PeriodInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Period Eloquent Model
 * 
 * Implements PeriodInterface from Nexus\Period package.
 */
class Period extends Model implements PeriodInterface
{
    use HasUlids;

    protected $table = 'periods';

    protected $fillable = [
        'type',
        'status',
        'start_date',
        'end_date',
        'fiscal_year',
        'name',
        'description',
    ];

    protected $casts = [
        'type' => PeriodType::class,
        'status' => PeriodStatus::class,
        'start_date' => 'datetime:Y-m-d',
        'end_date' => 'datetime:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): PeriodType
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): PeriodStatus
    {
        return $this->status;
    }

    /**
     * {@inheritDoc}
     */
    public function getStartDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->start_date);
    }

    /**
     * {@inheritDoc}
     */
    public function getEndDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->end_date);
    }

    /**
     * {@inheritDoc}
     */
    public function getFiscalYear(): string
    {
        return $this->fiscal_year;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function containsDate(DateTimeImmutable $date): bool
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();
        
        return $date >= $start && $date <= $end;
    }

    /**
     * {@inheritDoc}
     */
    public function isPostingAllowed(): bool
    {
        return $this->status->isPostingAllowed();
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    /**
     * {@inheritDoc}
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at);
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, PeriodType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, PeriodStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by fiscal year
     */
    public function scopeForFiscalYear($query, string $fiscalYear)
    {
        return $query->where('fiscal_year', $fiscalYear);
    }

    /**
     * Scope to find open periods
     */
    public function scopeOpen($query)
    {
        return $query->where('status', PeriodStatus::Open);
    }

    /**
     * Scope to find periods containing a date
     */
    public function scopeContainingDate($query, DateTimeImmutable $date)
    {
        $dateString = $date->format('Y-m-d');
        return $query->where('start_date', '<=', $dateString)
                    ->where('end_date', '>=', $dateString);
    }
}
