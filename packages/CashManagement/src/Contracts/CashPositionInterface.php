<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use DateTimeImmutable;
use Nexus\CashManagement\ValueObjects\CashPosition;

/**
 * Cash Position Interface
 *
 * Service for calculating real-time cash positions.
 */
interface CashPositionInterface
{
    /**
     * Get current cash position for a bank account
     */
    public function getCashPosition(string $bankAccountId): CashPosition;

    /**
     * Get cash position as of a specific date
     */
    public function getCashPositionAsOf(string $bankAccountId, DateTimeImmutable $asOfDate): CashPosition;

    /**
     * Get consolidated cash position across all bank accounts
     */
    public function getConsolidatedCashPosition(string $tenantId): CashPosition;

    /**
     * Get consolidated cash position in functional currency
     */
    public function getConsolidatedCashPositionInFunctionalCurrency(
        string $tenantId,
        string $functionalCurrency
    ): CashPosition;
}
