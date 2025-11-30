<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\ValueObjects;

/**
 * Individual line item in a financial statement.
 *
 * Represents a single account or grouping with hierarchy support.
 */
final readonly class StatementLineItem
{
    public function __construct(
        private string $code,
        private string $label,
        private float $amount,
        private int $level,
        private ?string $parentCode = null,
        private bool $isBold = false,
        private bool $isTotal = false,
        private ?array $metadata = null
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getParentCode(): ?string
    {
        return $this->parentCode;
    }

    public function isBold(): bool
    {
        return $this->isBold;
    }

    public function isTotal(): bool
    {
        return $this->isTotal;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Check if this is a parent item.
     */
    public function isParent(): bool
    {
        return $this->level === 0 || $this->isTotal;
    }

    /**
     * Get indentation spaces for display.
     */
    public function getIndentation(): string
    {
        return str_repeat('  ', $this->level);
    }

    /**
     * Format amount with currency.
     */
    public function formatAmount(string $currency = 'MYR', int $decimals = 2): string
    {
        return $currency . ' ' . number_format($this->amount, $decimals);
    }

    /**
     * Create a new instance with modified amount.
     */
    public function withAmount(float $amount): self
    {
        return new self(
            $this->code,
            $this->label,
            $amount,
            $this->level,
            $this->parentCode,
            $this->isBold,
            $this->isTotal,
            $this->metadata
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'amount' => $this->amount,
            'level' => $this->level,
            'parent_code' => $this->parentCode,
            'is_bold' => $this->isBold,
            'is_total' => $this->isTotal,
            'metadata' => $this->metadata,
        ];
    }
}
