<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Services;

use Nexus\FinancialStatements\Entities\StatementSection;
use Nexus\FinancialStatements\ValueObjects\LineItem;

/**
 * Pure logic for grouping line items into sections.
 */
final readonly class SectionGrouper
{
    /**
     * Group line items by account category.
     *
     * @param array<LineItem> $lineItems
     * @param array<string, string> $categoryMappings Account code prefix to category
     * @return array<string, StatementSection>
     */
    public function groupByCategory(array $lineItems, array $categoryMappings): array
    {
        $grouped = [];

        foreach ($lineItems as $lineItem) {
            $category = $this->determineCategory($lineItem->getAccountCode(), $categoryMappings);
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            
            $grouped[$category][] = $lineItem;
        }

        $sections = [];
        foreach ($grouped as $category => $items) {
            $sections[$category] = new StatementSection(
                code: $category,
                name: ucfirst($category),
                lineItems: $items
            );
        }

        return $sections;
    }

    /**
     * Group line items into hierarchical sections based on account structure.
     *
     * @param array<LineItem> $lineItems
     * @param int $depth Account code depth for grouping
     * @return array<string, StatementSection>
     */
    public function groupHierarchically(array $lineItems, int $depth = 2): array
    {
        $grouped = [];

        foreach ($lineItems as $lineItem) {
            $prefix = substr($lineItem->getAccountCode(), 0, $depth);
            
            if (!isset($grouped[$prefix])) {
                $grouped[$prefix] = [];
            }
            
            $grouped[$prefix][] = $lineItem;
        }

        $sections = [];
        foreach ($grouped as $prefix => $items) {
            $sections[$prefix] = new StatementSection(
                code: $prefix,
                name: "Section {$prefix}",
                lineItems: $items
            );
        }

        return $sections;
    }

    private function determineCategory(string $accountCode, array $categoryMappings): string
    {
        foreach ($categoryMappings as $prefix => $category) {
            if (str_starts_with($accountCode, $prefix)) {
                return $category;
            }
        }

        return 'uncategorized';
    }
}
