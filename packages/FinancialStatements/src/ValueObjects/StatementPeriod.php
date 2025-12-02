<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\ValueObjects;

/**
 * Represents a period context for financial statements.
 */
final readonly class StatementPeriod
{
    public function __construct(
        private string $periodId,
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $endDate,
        private string $fiscalYear,
        private int $periodNumber,
        private bool $isYearEnd = false,
    ) {}

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getFiscalYear(): string
    {
        return $this->fiscalYear;
    }

    public function getPeriodNumber(): int
    {
        return $this->periodNumber;
    }

    public function isYearEnd(): bool
    {
        return $this->isYearEnd;
    }
}
