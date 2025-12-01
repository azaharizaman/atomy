<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\ValueObjects;

/**
 * Represents a currency translation adjustment.
 */
final readonly class TranslationAdjustment
{
    public function __construct(
        private string $entityId,
        private string $fromCurrency,
        private string $toCurrency,
        private float $amount,
        private \DateTimeImmutable $asOfDate,
    ) {}

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getFromCurrency(): string
    {
        return $this->fromCurrency;
    }

    public function getToCurrency(): string
    {
        return $this->toCurrency;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getAsOfDate(): \DateTimeImmutable
    {
        return $this->asOfDate;
    }
}
