<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

/**
 * Represents an internal transaction for reconciliation purposes.
 */
final readonly class InternalTransaction
{
    /**
     * @param string $id Transaction identifier
     * @param float $amount Transaction amount
     * @param \DateTimeImmutable $date Transaction date
     * @param string $reference Transaction reference
     * @param array<string, mixed> $metadata Additional transaction metadata
     */
    public function __construct(
        private string $id,
        private float $amount,
        private \DateTimeImmutable $date,
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

    public function getDate(): \DateTimeImmutable
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
