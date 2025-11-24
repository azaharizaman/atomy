<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Currency\ValueObjects\Money;

/**
 * Nexus Threshold: Revenue/transaction thresholds for economic nexus
 * 
 * Represents the threshold that triggers tax collection obligation in a jurisdiction.
 * Used to determine when a business has "economic nexus" (tax presence) in a state/country.
 * 
 * Immutable and validated on construction.
 */
final readonly class NexusThreshold
{
    /**
     * @param string $jurisdictionCode Jurisdiction code (e.g., "US-CA", "EU-DE")
     * @param Money $revenueThreshold Annual revenue threshold
     * @param int|null $transactionThreshold Annual transaction count threshold (null = not applicable)
     * @param \DateTimeImmutable $effectiveFrom When this threshold becomes effective
     * @param \DateTimeImmutable|null $effectiveTo When this threshold expires (null = current)
     * @param string $calculationPeriod Period for threshold calculation (e.g., "trailing_12_months", "calendar_year")
     * @param array<string, mixed> $metadata Optional custom metadata
     */
    public function __construct(
        public string $jurisdictionCode,
        public Money $revenueThreshold,
        public ?int $transactionThreshold = null,
        public \DateTimeImmutable $effectiveFrom = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $effectiveTo = null,
        public string $calculationPeriod = 'trailing_12_months',
        public array $metadata = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->jurisdictionCode)) {
            throw new \InvalidArgumentException('Jurisdiction code cannot be empty');
        }

        if ($this->revenueThreshold->isNegative()) {
            throw new \InvalidArgumentException('Revenue threshold cannot be negative');
        }

        if ($this->transactionThreshold !== null && $this->transactionThreshold < 0) {
            throw new \InvalidArgumentException('Transaction threshold cannot be negative');
        }

        if ($this->effectiveTo !== null && $this->effectiveTo <= $this->effectiveFrom) {
            throw new \InvalidArgumentException(
                "effectiveTo must be after effectiveFrom"
            );
        }
    }

    /**
     * Check if revenue exceeds threshold
     */
    public function isRevenueExceeded(Money $revenue): bool
    {
        if ($revenue->getCurrency() !== $this->revenueThreshold->getCurrency()) {
            throw new \InvalidArgumentException(
                "Revenue currency ({$revenue->getCurrency()}) does not match threshold currency ({$this->revenueThreshold->getCurrency()})"
            );
        }

        return $revenue->greaterThanOrEqual($this->revenueThreshold);
    }

    /**
     * Check if transaction count exceeds threshold
     */
    public function isTransactionCountExceeded(int $transactionCount): bool
    {
        if ($this->transactionThreshold === null) {
            return false; // No transaction threshold defined
        }

        return $transactionCount >= $this->transactionThreshold;
    }

    /**
     * Check if either threshold is exceeded (logical OR)
     */
    public function isNexusTriggered(Money $revenue, int $transactionCount): bool
    {
        return $this->isRevenueExceeded($revenue) || $this->isTransactionCountExceeded($transactionCount);
    }

    /**
     * Get percentage of threshold reached for revenue
     */
    public function getRevenuePercentage(Money $revenue): string
    {
        if ($this->revenueThreshold->isZero()) {
            return '0.0000';
        }

        $percentage = bcdiv($revenue->getAmount(), $this->revenueThreshold->getAmount(), 6);
        return bcmul($percentage, '100', 4);
    }

    /**
     * Get percentage of threshold reached for transactions
     */
    public function getTransactionPercentage(int $transactionCount): string
    {
        if ($this->transactionThreshold === null || $this->transactionThreshold === 0) {
            return '0.0000';
        }

        $percentage = bcdiv((string) $transactionCount, (string) $this->transactionThreshold, 6);
        return bcmul($percentage, '100', 4);
    }

    public function toArray(): array
    {
        return [
            'jurisdiction_code' => $this->jurisdictionCode,
            'revenue_threshold' => $this->revenueThreshold->getAmount(),
            'currency' => $this->revenueThreshold->getCurrency(),
            'transaction_threshold' => $this->transactionThreshold,
            'effective_from' => $this->effectiveFrom->format('Y-m-d'),
            'effective_to' => $this->effectiveTo?->format('Y-m-d'),
            'calculation_period' => $this->calculationPeriod,
            'metadata' => $this->metadata,
        ];
    }
}
