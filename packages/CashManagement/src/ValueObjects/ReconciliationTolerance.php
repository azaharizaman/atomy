<?php

declare(strict_types=1);

namespace Nexus\CashManagement\ValueObjects;

use InvalidArgumentException;

/**
 * Reconciliation Tolerance Value Object
 *
 * Defines acceptable variance thresholds for automatic transaction matching.
 */
final readonly class ReconciliationTolerance
{
    public function __construct(
        private string $amountTolerance,
        private int $dateTolerance
    ) {
        $this->validate();
    }

    /**
     * Validate tolerance values
     */
    private function validate(): void
    {
        if (bccomp($this->amountTolerance, '0', 4) < 0) {
            throw new InvalidArgumentException('Amount tolerance cannot be negative');
        }

        if ($this->dateTolerance < 0) {
            throw new InvalidArgumentException('Date tolerance cannot be negative');
        }

        if ($this->dateTolerance > 30) {
            throw new InvalidArgumentException('Date tolerance cannot exceed 30 days');
        }
    }

    /**
     * Get amount tolerance as decimal string
     */
    public function getAmountTolerance(): string
    {
        return $this->amountTolerance;
    }

    /**
     * Get date tolerance in days
     */
    public function getDateTolerance(): int
    {
        return $this->dateTolerance;
    }

    /**
     * Check if amount variance is within tolerance
     */
    public function isAmountWithinTolerance(string $amount1, string $amount2): bool
    {
        $variance = bcsub($amount1, $amount2, 4);
        $absVariance = bccomp($variance, '0', 4) < 0 ? bcmul($variance, '-1', 4) : $variance;
        
        return bccomp($absVariance, $this->amountTolerance, 4) <= 0;
    }

    /**
     * Check if date variance is within tolerance
     */
    public function isDateWithinTolerance(\DateTimeImmutable $date1, \DateTimeImmutable $date2): bool
    {
        $diff = abs($date1->diff($date2)->days);
        return $diff <= $this->dateTolerance;
    }

    /**
     * Create default tolerance (0.01 amount, 3 days)
     */
    public static function default(): self
    {
        return new self('0.01', 3);
    }

    /**
     * Create strict tolerance (no variance allowed)
     */
    public static function strict(): self
    {
        return new self('0.00', 0);
    }
}
