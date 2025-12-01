<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Entities;

use Nexus\FinancialStatements\ValueObjects\LineItem;

/**
 * Represents a section within a financial statement.
 */
final readonly class StatementSection
{
    /**
     * @param string $code
     * @param string $name
     * @param array<LineItem> $lineItems
     * @param array<StatementSection> $subSections
     */
    public function __construct(
        private string $code,
        private string $name,
        private array $lineItems = [],
        private array $subSections = [],
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
     * @return array<LineItem>
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    /**
     * @return array<StatementSection>
     */
    public function getSubSections(): array
    {
        return $this->subSections;
    }

    public function getTotal(): float
    {
        $total = 0.0;
        
        foreach ($this->lineItems as $lineItem) {
            $total += $lineItem->getAmount();
        }
        
        foreach ($this->subSections as $subSection) {
            $total += $subSection->getTotal();
        }
        
        return $total;
    }
}
