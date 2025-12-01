<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\ValueObjects;

/**
 * Specification for a closing entry.
 */
final readonly class ClosingEntrySpec
{
    public function __construct(
        private string $description,
        private string $debitAccountCode,
        private string $creditAccountCode,
        private float $amount,
        private \DateTimeImmutable $entryDate,
        private ?string $reference = null,
    ) {}

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDebitAccountCode(): string
    {
        return $this->debitAccountCode;
    }

    public function getCreditAccountCode(): string
    {
        return $this->creditAccountCode;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getEntryDate(): \DateTimeImmutable
    {
        return $this->entryDate;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }
}
