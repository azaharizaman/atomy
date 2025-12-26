<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

final class BankStatement implements BankStatementInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $connectionId,
        private readonly \DateTimeImmutable $startDate,
        private readonly \DateTimeImmutable $endDate,
        private readonly float $amount,
        private readonly array $transactions = []
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
