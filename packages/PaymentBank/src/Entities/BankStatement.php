<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

final readonly class BankStatement implements BankStatementInterface
{
    public function __construct(
        private string $id,
        private string $connectionId,
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $endDate,
        private float $amount,
        private array $transactions = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }
}
