<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Data transfer object for accrual adjustment operations.
 */
final readonly class AccrualAdjustmentData
{
    public function __construct(
        public string $adjustmentType, // 'INCREASE', 'DECREASE', 'WRITE_OFF', 'REVERSAL'
        public Money $adjustmentAmount,
        public string $reason,
        public string $adjustedBy,
        public ?string $referenceDocument = null,
        public array $metadata = [],
    ) {}

    /**
     * Check if this is a write-off adjustment.
     */
    public function isWriteOff(): bool
    {
        return $this->adjustmentType === 'WRITE_OFF';
    }

    /**
     * Check if this is a reversal adjustment.
     */
    public function isReversal(): bool
    {
        return $this->adjustmentType === 'REVERSAL';
    }

    /**
     * Check if this adjustment increases the accrual.
     */
    public function isIncrease(): bool
    {
        return $this->adjustmentType === 'INCREASE';
    }

    /**
     * Check if this adjustment decreases the accrual.
     */
    public function isDecrease(): bool
    {
        return in_array($this->adjustmentType, ['DECREASE', 'WRITE_OFF', 'REVERSAL'], true);
    }
}
