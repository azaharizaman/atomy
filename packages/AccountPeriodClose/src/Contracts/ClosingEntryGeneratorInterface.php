<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Contracts;

use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;

/**
 * Contract for generating closing entries.
 */
interface ClosingEntryGeneratorInterface
{
    /**
     * Generate closing entries for a period.
     *
     * @param string $periodId
     * @param array<string, float> $incomeStatementBalances
     * @param string $retainedEarningsAccount
     * @return array<ClosingEntrySpec>
     */
    public function generate(
        string $periodId,
        array $incomeStatementBalances,
        string $retainedEarningsAccount
    ): array;

    /**
     * Generate year-end closing entries.
     *
     * @param string $periodId
     * @param array<string, float> $balances
     * @return array<ClosingEntrySpec>
     */
    public function generateYearEnd(string $periodId, array $balances): array;
}
