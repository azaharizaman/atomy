<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

final readonly class BankTransaction
{
    /**
     * @param string $id Transaction identifier
     * @param string $accountId Associated account identifier
     * @param \DateTimeImmutable $date Transaction date
     * @param float $amount Transaction amount
     * @param string $description Transaction description
     * @param string|null $merchantName Merchant name (if available)
     * @param string|null $category Transaction category (if categorized)
     * @param array<string, mixed> $metadata Additional transaction metadata
     */
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
