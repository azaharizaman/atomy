<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

final readonly class BankStatement implements BankStatementInterface
{
    /**
     * @param string $id
     * @param string $connectionId
     * @param \DateTimeImmutable $startDate
     * @param \DateTimeImmutable $endDate
     * @param float $amount Note: Using float for monetary amounts is not ideal. Consider using Money value objects for exact precision.
     * @param array $transactions
     */
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
