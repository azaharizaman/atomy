<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\Engine\Models;

use Nexus\Accounting\Contracts\IncomeStatementInterface;
use Nexus\Accounting\Core\ValueObjects\{ReportingPeriod, StatementSection};
use Nexus\Accounting\Core\Enums\StatementType;

/**
 * Income Statement implementation.
 */
final readonly class IncomeStatement implements IncomeStatementInterface
{
    /**
     * @param StatementSection[] $sections
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $entityId,
        private ReportingPeriod $period,
        private array $sections,
        private array $metadata = [],
        private bool $locked = false
    ) {}

    public function getType(): StatementType
    {
        return StatementType::INCOME_STATEMENT;
    }

    public function getReportingPeriod(): ReportingPeriod
    {
        return $this->period;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function getSection(string $name): ?StatementSection
    {
        foreach ($this->sections as $section) {
            if ($section->getCode() === $name || $section->getName() === $name) {
                return $section;
            }
        }
        return null;
    }

    public function getGrandTotal(): float
    {
        return $this->getNetIncome();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType()->value,
            'entity_id' => $this->entityId,
            'period' => $this->period->toArray(),
            'sections' => array_map(fn($s) => $s->toArray(), $this->sections),
            'totals' => [
                'revenue' => $this->getTotalRevenue(),
                'cogs' => $this->getCostOfGoodsSold(),
                'gross_profit' => $this->getGrossProfit(),
                'gross_profit_margin' => $this->getGrossProfitMargin(),
                'operating_expenses' => $this->getTotalOperatingExpenses(),
                'operating_income' => $this->getOperatingIncome(),
                'other_income' => $this->getOtherIncome(),
                'income_before_tax' => $this->getIncomeBeforeTax(),
                'tax_expense' => $this->getTaxExpense(),
                'net_income' => $this->getNetIncome(),
            ],
            'metadata' => $this->metadata,
            'locked' => $this->locked,
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function getTotalRevenue(): float
    {
        $revenueSection = $this->getSection('REVENUE');
        return $revenueSection?->getTotal() ?? 0.0;
    }

    public function getCostOfGoodsSold(): float
    {
        $cogsSection = $this->getSection('COGS');
        return abs($cogsSection?->getTotal() ?? 0.0);
    }

    public function getGrossProfit(): float
    {
        return $this->getTotalRevenue() - $this->getCostOfGoodsSold();
    }

    public function getGrossProfitMargin(): float
    {
        $revenue = $this->getTotalRevenue();
        if ($revenue == 0) {
            return 0.0;
        }
        return ($this->getGrossProfit() / $revenue) * 100;
    }

    public function getTotalOperatingExpenses(): float
    {
        $expenseSection = $this->getSection('EXPENSES');
        return abs($expenseSection?->getTotal() ?? 0.0);
    }

    public function getOperatingIncome(): float
    {
        return $this->getGrossProfit() - $this->getTotalOperatingExpenses();
    }

    public function getOtherIncome(): float
    {
        $otherSection = $this->getSection('OTHER');
        return $otherSection?->getTotal() ?? 0.0;
    }

    public function getIncomeBeforeTax(): float
    {
        return $this->getOperatingIncome() + $this->getOtherIncome();
    }

    public function getTaxExpense(): float
    {
        // Look for tax expense in metadata or calculate from a tax section
        return abs($this->metadata['tax_expense'] ?? 0.0);
    }

    public function getNetIncome(): float
    {
        return $this->getIncomeBeforeTax() - $this->getTaxExpense();
    }

    public function getEarningsPerShare(): ?float
    {
        return $this->metadata['earnings_per_share'] ?? null;
    }
}
