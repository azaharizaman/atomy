<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

final readonly class BankTransaction
{
    public function __construct(
        private string $id,
        private string $accountId,
        private \DateTimeImmutable $date,
        private float $amount,
        private string $description,
        private ?string $merchantName = null,
        private ?string $category = null,
        private array $metadata = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
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

    public function getMerchantName(): ?string
    {
        return $this->merchantName;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
