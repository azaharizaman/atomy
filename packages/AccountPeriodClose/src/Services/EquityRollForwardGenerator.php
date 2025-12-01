<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Services;

use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;

/**
 * Pure logic for generating equity roll-forward entries.
 */
final readonly class EquityRollForwardGenerator
{
    /**
     * Generate equity roll-forward entries.
     *
     * @param string $periodId
     * @param array<string, float> $equityBalances Opening equity balances
     * @param float $netIncome Net income for the period
     * @param float $dividends Dividends declared
     * @param array<string, float> $otherComprehensiveIncome OCI items
     * @return array<ClosingEntrySpec>
     */
    public function generate(
        string $periodId,
        array $equityBalances,
        float $netIncome,
        float $dividends = 0.0,
        array $otherComprehensiveIncome = []
    ): array {
        $entries = [];
        $entryDate = new \DateTimeImmutable();

        if ($dividends > 0) {
            $entries[] = new ClosingEntrySpec(
                description: 'Record dividends declared',
                debitAccountCode: '3200',
                creditAccountCode: '2100',
                amount: $dividends,
                entryDate: $entryDate,
                reference: "EQUITY-{$periodId}"
            );
        }

        foreach ($otherComprehensiveIncome as $accountCode => $amount) {
            if ($amount > 0) {
                $entries[] = new ClosingEntrySpec(
                    description: "Record OCI: {$accountCode}",
                    debitAccountCode: $accountCode,
                    creditAccountCode: '3300',
                    amount: $amount,
                    entryDate: $entryDate,
                    reference: "OCI-{$periodId}"
                );
            } elseif ($amount < 0) {
                $entries[] = new ClosingEntrySpec(
                    description: "Record OCI: {$accountCode}",
                    debitAccountCode: '3300',
                    creditAccountCode: $accountCode,
                    amount: abs($amount),
                    entryDate: $entryDate,
                    reference: "OCI-{$periodId}"
                );
            }
        }

        return $entries;
    }
}
