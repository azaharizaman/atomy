<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\ValueObjects;

/**
 * Represents a section of a financial statement.
 */
final readonly class StatementSection
{
    /**
     * @param LineItem[] $lineItems
     * @param StatementSection[] $subSections
     */
    public function __construct(
        private string $code,
        private string $name,
        private array $lineItems = [],
        private array $subSections = [],
        private int $displayOrder = 0
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
     * @return LineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    /**
     * @return StatementSection[]
     */
    public function getSubSections(): array
    {
        return $this->subSections;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    /**
     * Get the total of all line items in this section (including sub-sections).
     */
    public function getTotal(): float
    {
        $total = 0.0;

        foreach ($this->lineItems as $item) {
            $total += $item->getAmount();
        }

        foreach ($this->subSections as $subSection) {
            $total += $subSection->getTotal();
        }

        return $total;
    }

    /**
     * Add a line item to this section.
     *
     * @return self
     */
    public function withLineItem(LineItem $item): self
    {
        $items = $this->lineItems;
        $items[] = $item;

        return new self(
            $this->code,
            $this->name,
            $items,
            $this->subSections,
            $this->displayOrder
        );
    }

    /**
     * Add a sub-section to this section.
     *
     * @return self
     */
    public function withSubSection(StatementSection $subSection): self
    {
        $subSections = $this->subSections;
        $subSections[] = $subSection;

        return new self(
            $this->code,
            $this->name,
            $this->lineItems,
            $subSections,
            $this->displayOrder
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
            'code' => $this->code,
            'name' => $this->name,
            'line_items' => array_map(fn($i) => $i->toArray(), $this->lineItems),
            'sub_sections' => array_map(fn($s) => $s->toArray(), $this->subSections),
            'display_order' => $this->displayOrder,
            'total' => $this->getTotal(),
        ];
    }
}
