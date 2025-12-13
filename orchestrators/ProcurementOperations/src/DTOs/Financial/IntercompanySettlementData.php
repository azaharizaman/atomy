<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Represents an intercompany transaction for settlement.
 */
final readonly class IntercompanySettlementData
{
    public function __construct(
        public string $transactionId,
        public string $fromEntityId,
        public string $toEntityId,
        public string $transactionType, // PURCHASE, SALE, SERVICE, ALLOCATION
        public Money $originalAmount,
        public string $originalCurrency,
        public Money $settlementAmount,
        public string $settlementCurrency,
        public \DateTimeImmutable $transactionDate,
        public ?string $referenceDocument = null,
        public ?string $description = null,
        public array $metadata = [],
    ) {}

    /**
     * Check if this is a receivable from fromEntity's perspective.
     */
    public function isReceivable(): bool
    {
        return in_array($this->transactionType, ['SALE', 'SERVICE_PROVIDED'], true);
    }

    /**
     * Check if this is a payable from fromEntity's perspective.
     */
    public function isPayable(): bool
    {
        return in_array($this->transactionType, ['PURCHASE', 'SERVICE_RECEIVED', 'ALLOCATION'], true);
    }

    /**
     * Check if currency translation was applied.
     */
    public function requiresCurrencyTranslation(): bool
    {
        return $this->originalCurrency !== $this->settlementCurrency;
    }

    /**
     * Get age in days from transaction date.
     */
    public function getAgeDays(\DateTimeImmutable $asOfDate = new \DateTimeImmutable()): int
    {
        return $this->transactionDate->diff($asOfDate)->days;
    }
}
