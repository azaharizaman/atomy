<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\ValueObjects;

/**
 * Reporting period with date range and comparison support.
 *
 * Immutable value object representing a financial reporting period.
 */
final readonly class ReportingPeriod
{
    public function __construct(
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $endDate,
        private string $label,
        private ?string $fiscalYearId = null
    ) {
        if ($this->startDate > $this->endDate) {
            throw new \InvalidArgumentException('Start date must be before or equal to end date');
        }
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getFiscalYearId(): ?string
    {
        return $this->fiscalYearId;
    }

    /**
     * Check if a date falls within this period.
     */
    public function contains(\DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /**
     * Check if this period overlaps with another.
     */
    public function overlaps(self $other): bool
    {
        return $this->startDate <= $other->endDate && $other->startDate <= $this->endDate;
    }

    /**
     * Get the number of days in this period.
     */
    public function getDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->format('%a') + 1;
    }

    /**
     * Check if this is a month period.
     */
    public function isMonth(): bool
    {
        return $this->startDate->format('Y-m-01') === $this->startDate->format('Y-m-d')
            && $this->endDate->format('Y-m-t') === $this->endDate->format('Y-m-d');
    }

    /**
     * Check if this is a quarter period.
     */
    public function isQuarter(): bool
    {
        return $this->getDays() >= 89 && $this->getDays() <= 92;
    }

    /**
     * Check if this is a year period.
     */
    public function isYear(): bool
    {
        return $this->getDays() >= 365 && $this->getDays() <= 366;
    }

    /**
     * Create a period from a month.
     */
    public static function forMonth(int $year, int $month): self
    {
        $start = new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $month));
        $end = new \DateTimeImmutable($start->format('Y-m-t'));
        
        return new self($start, $end, $start->format('F Y'));
    }

    /**
     * Create a period from a quarter.
     */
    public static function forQuarter(int $year, int $quarter): self
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $start = new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $startMonth));
        $endMonth = $startMonth + 2;
        $end = new \DateTimeImmutable(sprintf('%d-%02d-%s', $year, $endMonth, $start->format('t')));
        
        return new self($start, $end, sprintf('Q%d %d', $quarter, $year));
    }

    /**
     * Create a period from a year.
     */
    public static function forYear(int $year): self
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01', $year));
        $end = new \DateTimeImmutable(sprintf('%d-12-31', $year));
        
        return new self($start, $end, (string) $year);
    }

    /**
     * Create a custom period.
     */
    public static function custom(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        string $label
    ): self {
        return new self($start, $end, $label);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'label' => $this->label,
            'fiscal_year_id' => $this->fiscalYearId,
            'days' => $this->getDays(),
        ];
    }
}
