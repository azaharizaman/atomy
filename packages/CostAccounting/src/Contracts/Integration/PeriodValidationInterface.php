<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts\Integration;

/**
 * Period Validation Interface
 * 
 * Integration contract for Nexus\Period package.
 * Provides fiscal period validation.
 */
interface PeriodValidationInterface
{
    /**
     * Validate period exists
     * 
     * @param string $periodId Period identifier
     * @return bool
     */
    public function validatePeriod(string $periodId): bool;

    /**
     * Check if period is open
     * 
     * @param string $periodId Period identifier
     * @return bool
     */
    public function isPeriodOpen(string $periodId): bool;

    /**
     * Check if period is closed
     * 
     * @param string $periodId Period identifier
     * @return bool
     */
    public function isPeriodClosed(string $periodId): bool;

    /**
     * Get period date range
     * 
     * @param string $periodId Period identifier
     * @return array<string, string>
     */
    public function getPeriodDateRange(string $periodId): array;

    /**
     * Validate cost posting date
     * 
     * @param string $date Cost posting date
     * @param string $periodId Period identifier
     * @return bool
     */
    public function validateCostPostingDate(
        string $date,
        string $periodId
    ): bool;
}
