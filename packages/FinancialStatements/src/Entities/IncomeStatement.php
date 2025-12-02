<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Entities;

use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
use Nexus\FinancialStatements\ValueObjects\StatementMetadata;

/**
 * Income Statement (Profit and Loss Statement).
 */
final readonly class IncomeStatement implements FinancialStatementInterface
{
    /**
     * @param StatementMetadata $metadata
     * @param array<string, mixed> $revenues
     * @param array<string, mixed> $expenses
     * @param array<string, mixed> $otherIncome
     * @param array<string, mixed> $otherExpenses
     */
    public function __construct(
        private StatementMetadata $metadata,
        private array $revenues,
        private array $expenses,
        private array $otherIncome = [],
        private array $otherExpenses = [],
    ) {}

    public function getMetadata(): StatementMetadata
    {
        return $this->metadata;
    }

    public function getSections(): array
    {
        return [
            'revenues' => $this->revenues,
            'expenses' => $this->expenses,
            'other_income' => $this->otherIncome,
            'other_expenses' => $this->otherExpenses,
        ];
    }

    public function getRevenues(): array
    {
        return $this->revenues;
    }

    public function getExpenses(): array
    {
        return $this->expenses;
    }

    public function getNetIncome(): float
    {
        $totalRevenue = $this->calculateSectionTotal($this->revenues)
            + $this->calculateSectionTotal($this->otherIncome);
        $totalExpenses = $this->calculateSectionTotal($this->expenses)
            + $this->calculateSectionTotal($this->otherExpenses);

        return $totalRevenue - $totalExpenses;
    }

    public function isValid(): bool
    {
        return true;
    }

    private function calculateSectionTotal(array $section): float
    {
        $total = 0.0;
        foreach ($section as $item) {
            if (is_array($item) && isset($item['amount'])) {
                $total += (float) $item['amount'];
            }
        }
        return $total;
    }
}
