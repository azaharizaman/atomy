<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

use Nexus\PaymentBank\Enums\AccountType;

final readonly class BankAccount
{
    /**
     * @param string $id Account identifier
     * @param string $name Account display name
     * @param string $accountNumber Account number
     * @param string $routingNumber Bank routing number
     * @param AccountType $type Account type (checking, savings, etc.)
     * @param BankAccountBalance $balance Current account balance
     * @param array<string, mixed> $metadata Additional account metadata
     */
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
