<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Services;

/**
 * Pure calculation logic for consolidation.
 */
final readonly class ConsolidationCalculator
{
    /**
     * Sum balances from multiple entities.
     *
     * @param array<array<string, float>> $entityBalances
     * @return array<string, float>
     */
    public function sumBalances(array $entityBalances): array
    {
        $consolidated = [];

        foreach ($entityBalances as $balances) {
            foreach ($balances as $accountCode => $amount) {
                $consolidated[$accountCode] = ($consolidated[$accountCode] ?? 0.0) + $amount;
            }
        }

        return $consolidated;
    }

    /**
     * Apply proportional consolidation based on ownership.
     *
     * @param array<string, float> $balances
     * @param float $ownershipPercentage
     * @return array<string, float>
     */
    public function applyProportional(array $balances, float $ownershipPercentage): array
    {
        $factor = $ownershipPercentage / 100.0;
        $result = [];

        foreach ($balances as $accountCode => $amount) {
            $result[$accountCode] = $amount * $factor;
        }

        return $result;
    }

    /**
     * Calculate net elimination impact.
     *
     * @param array<array<string, float>> $eliminations
     * @return array<string, float>
     */
    public function calculateNetEliminations(array $eliminations): array
    {
        $net = [];

        foreach ($eliminations as $elimination) {
            foreach ($elimination as $accountCode => $amount) {
                $net[$accountCode] = ($net[$accountCode] ?? 0.0) + $amount;
            }
        }

        return $net;
    }
}
