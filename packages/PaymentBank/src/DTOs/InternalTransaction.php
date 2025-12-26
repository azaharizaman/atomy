<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

/**
 * Represents an internal transaction for reconciliation purposes.
 */
final readonly class InternalTransaction
{
    public function __construct(
        private string $id,
        private float $amount,
        private string $date,
        private string $reference,
        private array $metadata = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
