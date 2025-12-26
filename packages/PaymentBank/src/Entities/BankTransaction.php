<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

final class BankTransaction implements BankTransactionInterface
{
    public function __construct(
        private readonly string $id,
        private readonly \DateTimeImmutable $date,
        private readonly float $amount,
        private readonly string $description
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
