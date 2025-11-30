<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\ValueObjects;

/**
 * Represents a financial reporting period.
 */
final readonly class ReportingPeriod
{
    public function __construct(
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $endDate,
        private string $label,
        private ?string $periodId = null
    ) {
        if ($endDate < $startDate) {
            throw new \InvalidArgumentException('End date must be after start date');
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

    public function getPeriodId(): ?string
    {
        return $this->periodId;
    }

    /**
     * Create a monthly period.
     */
    public static function forMonth(int $year, int $month): self
    {
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('last day of this month');
        $label = $start->format('F Y');

        return new self($start, $end, $label);
    }

    /**
     * Create a quarterly period.
     */
    public static function forQuarter(int $year, int $quarter): self
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new \InvalidArgumentException('Quarter must be between 1 and 4');
        }

        $startMonth = (($quarter - 1) * 3) + 1;
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $startMonth));
        $end = $start->modify('+2 months')->modify('last day of this month');
        $label = "Q{$quarter} {$year}";

        return new self($start, $end, $label);
    }

    /**
     * Create a yearly period.
     */
    public static function forYear(int $year): self
    {
        $start = new \DateTimeImmutable("{$year}-01-01");
        $end = new \DateTimeImmutable("{$year}-12-31");
        $label = "FY {$year}";

        return new self($start, $end, $label);
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
     * Check if a date falls within this period.
     */
    public function contains(\DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /**
     * Get the number of days in the period.
     */
    public function getDayCount(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days + 1;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'label' => $this->label,
            'period_id' => $this->periodId,
            'days' => $this->getDayCount(),
        ];
    }
}
