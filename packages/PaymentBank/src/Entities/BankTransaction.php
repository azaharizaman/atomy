<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

final readonly class BankTransaction implements BankTransactionInterface
{
    public function __construct(
        private string $id,
        private \DateTimeImmutable $date,
        private float $amount,
        private string $description
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
