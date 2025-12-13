<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Result of netting calculation between two entities.
 */
final readonly class NetSettlementResult
{
    public function __construct(
        public string $settlementId,
        public string $fromEntityId,
        public string $toEntityId,
        public Money $grossReceivables,
        public Money $grossPayables,
        public Money $netAmount,
        public string $netDirection, // RECEIVE, PAY, ZERO
        public string $settlementCurrency,
        public int $receivablesCount,
        public int $payablesCount,
        /** @var array<string> Transaction IDs included */
        public array $includedTransactions,
        public \DateTimeImmutable $calculatedAt,
        public ?Money $currencyGainLoss = null,
    ) {}

    /**
     * Check if settlement results in payment.
     */
    public function requiresPayment(): bool
    {
        return $this->netDirection === 'PAY' && !$this->netAmount->isZero();
    }

    /**
     * Check if settlement results in receipt.
     */
    public function expectsReceipt(): bool
    {
        return $this->netDirection === 'RECEIVE' && !$this->netAmount->isZero();
    }

    /**
     * Check if positions are fully offset.
     */
    public function isFullyOffset(): bool
    {
        return $this->netDirection === 'ZERO' || $this->netAmount->isZero();
    }

    /**
     * Get netting efficiency percentage.
     */
    public function getNettingEfficiency(): float
    {
        $grossTotal = $this->grossReceivables->add($this->grossPayables);
        if ($grossTotal->isZero()) {
            return 100.0;
        }

        $netted = $grossTotal->subtract($this->netAmount);
        return round(($netted->getAmount() / $grossTotal->getAmount()) * 100, 2);
    }

    /**
     * Get total transaction count.
     */
    public function getTotalTransactionCount(): int
    {
        return $this->receivablesCount + $this->payablesCount;
    }
}
