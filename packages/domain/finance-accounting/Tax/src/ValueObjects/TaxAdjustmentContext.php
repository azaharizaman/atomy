<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Enums\TaxType;

/**
 * Tax Adjustment Context: Context for adjusting/reversing a previous tax calculation
 * 
 * Used for contra-transactions (credit notes, refunds, corrections).
 * References the original transaction being adjusted.
 * 
 * Immutable and validated on construction.
 */
final readonly class TaxAdjustmentContext
{
    /**
     * @param string $adjustmentId Unique adjustment identifier
     * @param string $originalTransactionId Original transaction being adjusted
     * @param \DateTimeImmutable $adjustmentDate When adjustment is made
     * @param string $reason Reason for adjustment (e.g., "Customer refund", "Calculation error")
     * @param Money $adjustmentAmount Amount to adjust (positive = increase tax, negative = decrease)
     * @param TaxType|null $taxType Tax type (inherited from original if null)
     * @param string|null $taxCode Tax code (inherited from original if null)
     * @param bool $isFullReversal Whether this is a full reversal of original transaction
     * @param array<string, mixed> $metadata Optional custom metadata
     */
    public function __construct(
        public string $adjustmentId,
        public string $originalTransactionId,
        public \DateTimeImmutable $adjustmentDate,
        public string $reason,
        public Money $adjustmentAmount,
        public ?TaxType $taxType = null,
        public ?string $taxCode = null,
        public bool $isFullReversal = false,
        public array $metadata = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->adjustmentId)) {
            throw new \InvalidArgumentException('Adjustment ID cannot be empty');
        }

        if (empty($this->originalTransactionId)) {
            throw new \InvalidArgumentException('Original transaction ID cannot be empty');
        }

        if (empty($this->reason)) {
            throw new \InvalidArgumentException('Adjustment reason cannot be empty');
        }
    }

    /**
     * Check if this is a positive adjustment (increases tax)
     */
    public function isPositiveAdjustment(): bool
    {
        return $this->adjustmentAmount->isPositive();
    }

    /**
     * Check if this is a negative adjustment (decreases tax / refund)
     */
    public function isNegativeAdjustment(): bool
    {
        return $this->adjustmentAmount->isNegative();
    }

    /**
     * Get absolute adjustment amount
     */
    public function getAbsoluteAmount(): Money
    {
        return Money::of(
            ltrim($this->adjustmentAmount->getAmount(), '-'),
            $this->adjustmentAmount->getCurrency()
        );
    }

    /**
     * Convert to TaxContext for calculation
     * 
     * Creates a TaxContext from this adjustment context, inheriting tax type/code if not set
     */
    public function toTaxContext(
        string $customerId,
        array $destinationAddress,
        TaxType $defaultTaxType,
        string $defaultTaxCode
    ): TaxContext {
        return new TaxContext(
            transactionId: $this->adjustmentId,
            transactionDate: $this->adjustmentDate,
            taxCode: $this->taxCode ?? $defaultTaxCode,
            taxType: $this->taxType ?? $defaultTaxType,
            customerId: $customerId,
            destinationAddress: $destinationAddress,
            metadata: array_merge($this->metadata, [
                'is_adjustment' => true,
                'original_transaction_id' => $this->originalTransactionId,
                'adjustment_reason' => $this->reason,
                'is_full_reversal' => $this->isFullReversal,
            ])
        );
    }

    public function toArray(): array
    {
        return [
            'adjustment_id' => $this->adjustmentId,
            'original_transaction_id' => $this->originalTransactionId,
            'adjustment_date' => $this->adjustmentDate->format('Y-m-d H:i:s'),
            'reason' => $this->reason,
            'adjustment_amount' => $this->adjustmentAmount->getAmount(),
            'currency' => $this->adjustmentAmount->getCurrency(),
            'tax_type' => $this->taxType?->value,
            'tax_code' => $this->taxCode,
            'is_full_reversal' => $this->isFullReversal,
            'is_positive_adjustment' => $this->isPositiveAdjustment(),
            'is_negative_adjustment' => $this->isNegativeAdjustment(),
            'metadata' => $this->metadata,
        ];
    }
}
