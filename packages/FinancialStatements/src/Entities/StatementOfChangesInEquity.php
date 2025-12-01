<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Entities;

use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
use Nexus\FinancialStatements\ValueObjects\StatementMetadata;

/**
 * Statement of Changes in Equity.
 */
final readonly class StatementOfChangesInEquity implements FinancialStatementInterface
{
    /**
     * @param StatementMetadata $metadata
     * @param array<string, mixed> $shareCapital
     * @param array<string, mixed> $retainedEarnings
     * @param array<string, mixed> $reserves
     * @param array<string, mixed> $comprehensiveIncome
     */
    public function __construct(
        private StatementMetadata $metadata,
        private array $shareCapital,
        private array $retainedEarnings,
        private array $reserves,
        private array $comprehensiveIncome = [],
    ) {}

    public function getMetadata(): StatementMetadata
    {
        return $this->metadata;
    }

    public function getSections(): array
    {
        return [
            'share_capital' => $this->shareCapital,
            'retained_earnings' => $this->retainedEarnings,
            'reserves' => $this->reserves,
            'comprehensive_income' => $this->comprehensiveIncome,
        ];
    }

    public function getShareCapital(): array
    {
        return $this->shareCapital;
    }

    public function getRetainedEarnings(): array
    {
        return $this->retainedEarnings;
    }

    public function getReserves(): array
    {
        return $this->reserves;
    }

    public function getTotalEquity(): float
    {
        return $this->calculateSectionTotal($this->shareCapital)
            + $this->calculateSectionTotal($this->retainedEarnings)
            + $this->calculateSectionTotal($this->reserves);
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
