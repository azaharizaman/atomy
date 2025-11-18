<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\ValueObjects;

/**
 * Statement section grouping.
 *
 * Represents a major section like Assets, Liabilities, Revenue, etc.
 */
final readonly class StatementSection
{
    /**
     * @param StatementLineItem[] $lineItems
     */
    public function __construct(
        private string $code,
        private string $name,
        private array $lineItems,
        private int $order,
        private ?string $parentCode = null
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return StatementLineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getParentCode(): ?string
    {
        return $this->parentCode;
    }

    /**
     * Calculate the total of all line items.
     */
    public function getTotal(): float
    {
        return array_reduce(
            $this->lineItems,
            fn(float $sum, StatementLineItem $item) => $sum + $item->getAmount(),
            0.0
        );
    }

    /**
     * Get line items at a specific level.
     *
     * @return StatementLineItem[]
     */
    public function getLineItemsByLevel(int $level): array
    {
        return array_filter(
            $this->lineItems,
            fn(StatementLineItem $item) => $item->getLevel() === $level
        );
    }

    /**
     * Get top-level line items only.
     *
     * @return StatementLineItem[]
     */
    public function getTopLevelItems(): array
    {
        return $this->getLineItemsByLevel(0);
    }

    /**
     * Check if section has any line items.
     */
    public function hasLineItems(): bool
    {
        return count($this->lineItems) > 0;
    }

    /**
     * Get the number of line items.
     */
    public function getLineItemCount(): int
    {
        return count($this->lineItems);
    }

    /**
     * Create a new section with additional line items.
     *
     * @param StatementLineItem[] $additionalItems
     */
    public function withLineItems(array $additionalItems): self
    {
        return new self(
            $this->code,
            $this->name,
            array_merge($this->lineItems, $additionalItems),
            $this->order,
            $this->parentCode
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
            'name' => $this->name,
            'order' => $this->order,
            'parent_code' => $this->parentCode,
            'total' => $this->getTotal(),
            'line_items' => array_map(
                fn(StatementLineItem $item) => $item->toArray(),
                $this->lineItems
            ),
        ];
    }
}
