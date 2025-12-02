<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Services;

use Nexus\AccountPeriodClose\Enums\CloseType;
use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;

/**
 * Pure domain logic for year-end close handling.
 */
final readonly class YearEndCloseHandler
{
    public function __construct(
        private ClosingEntryGenerator $closingEntryGenerator = new ClosingEntryGenerator(),
        private RetainedEarningsCalculator $retainedEarningsCalculator = new RetainedEarningsCalculator(),
        private EquityRollForwardGenerator $equityRollForwardGenerator = new EquityRollForwardGenerator(),
    ) {}

    /**
     * Generate all year-end closing entries.
     *
     * @param string $periodId
     * @param array<string, float> $incomeStatementBalances
     * @param array<string, float> $equityBalances
     * @param float $dividends
     * @return array<ClosingEntrySpec>
     */
    public function generateYearEndEntries(
        string $periodId,
        array $incomeStatementBalances,
        array $equityBalances,
        float $dividends = 0.0
    ): array {
        $entries = [];

        $closingEntries = $this->closingEntryGenerator->generateYearEnd(
            $periodId,
            $incomeStatementBalances
        );
        $entries = array_merge($entries, $closingEntries);

        $totalRevenue = array_sum(array_filter(
            $incomeStatementBalances,
            fn($code) => str_starts_with($code, '4'),
            ARRAY_FILTER_USE_KEY
        ));
        $totalExpenses = array_sum(array_filter(
            $incomeStatementBalances,
            fn($code) => str_starts_with($code, '5') || str_starts_with($code, '6'),
            ARRAY_FILTER_USE_KEY
        ));

        $netIncome = $this->retainedEarningsCalculator->calculateNetIncome(
            $totalRevenue,
            $totalExpenses
        );

        $equityEntries = $this->equityRollForwardGenerator->generate(
            $periodId,
            $equityBalances,
            $netIncome,
            $dividends
        );
        $entries = array_merge($entries, $equityEntries);

        return $entries;
    }

    /**
     * Determine the type of close based on period characteristics.
     */
    public function determineCloseType(int $periodMonth, bool $isFiscalYearEnd): CloseType
    {
        if ($isFiscalYearEnd) {
            return CloseType::YEARLY;
        }

        if (in_array($periodMonth, [3, 6, 9, 12])) {
            return CloseType::QUARTERLY;
        }

        return CloseType::MONTHLY;
    }
}
