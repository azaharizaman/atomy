<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

interface BankStatementInterface
{
    public function getId(): string;
    public function getConnectionId(): string;
    public function getStartDate(): \DateTimeImmutable;
    public function getEndDate(): \DateTimeImmutable;
    
    /**
     * Get the closing balance amount for the statement period.
     *
     * @return float
     */
    public function getAmount(): float;
    
    public function getTransactions(): array;
}
