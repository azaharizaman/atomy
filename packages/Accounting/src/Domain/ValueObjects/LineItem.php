<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\ValueObjects;

/**
 * Represents a line item in a financial statement section.
 */
final readonly class LineItem
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $accountCode,
        private string $accountName,
        private float $amount,
        private int $displayOrder = 0,
        private array $metadata = []
    ) {}

    public function getAccountCode(): string
    {
        return $this->accountCode;
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Create a new LineItem with a modified amount.
     */
    public function withAmount(float $amount): self
    {
        return new self(
            $this->accountCode,
            $this->accountName,
            $amount,
            $this->displayOrder,
            $this->metadata
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_code' => $this->accountCode,
            'account_name' => $this->accountName,
            'amount' => $this->amount,
            'display_order' => $this->displayOrder,
            'metadata' => $this->metadata,
        ];
    }
}
