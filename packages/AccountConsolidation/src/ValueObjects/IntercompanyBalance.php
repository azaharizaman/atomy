<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\ValueObjects;

/**
 * Represents an intercompany balance between two entities.
 */
final readonly class IntercompanyBalance
{
    public function __construct(
        private string $fromEntityId,
        private string $toEntityId,
        private string $accountCode,
        private float $amount,
        private string $transactionType,
    ) {}

    public function getFromEntityId(): string
    {
        return $this->fromEntityId;
    }

    public function getToEntityId(): string
    {
        return $this->toEntityId;
    }

    public function getAccountCode(): string
    {
        return $this->accountCode;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getTransactionType(): string
    {
        return $this->transactionType;
    }
}
