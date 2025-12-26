<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

use Nexus\PaymentBank\Enums\AccountType;

final readonly class BankAccount
{
    public function __construct(
        private string $id,
        private string $name,
        private string $accountNumber,
        private string $routingNumber,
        private AccountType $type,
        private BankAccountBalance $balance,
        private array $metadata = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function getRoutingNumber(): string
    {
        return $this->routingNumber;
    }

    public function getType(): AccountType
    {
        return $this->type;
    }

    public function getBalance(): BankAccountBalance
    {
        return $this->balance;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
