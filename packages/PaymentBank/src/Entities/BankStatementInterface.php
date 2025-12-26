<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

interface BankStatementInterface
{
    public function getId(): string;
    public function getConnectionId(): string;
    public function getStartDate(): \DateTimeImmutable;
    public function getEndDate(): \DateTimeImmutable;
    public function getAmount(): float; // Or Money? Using float for now based on test array
    public function getTransactions(): array;
}
