<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Rules;

use Nexus\AccountConsolidation\Contracts\EliminationRuleInterface;
use Nexus\AccountConsolidation\Enums\EliminationType;
use Nexus\AccountConsolidation\ValueObjects\EliminationEntry;

/**
 * Elimination rule for intercompany revenue.
 */
final readonly class IntercompanyRevenueRule implements EliminationRuleInterface
{
    public function getId(): string
    {
        return 'intercompany_revenue';
    }

    public function getName(): string
    {
        return 'Intercompany Revenue Elimination';
    }

    public function apply(array $consolidationData): array
    {
        $eliminations = [];

        foreach ($consolidationData['balances'] ?? [] as $balance) {
            foreach ($balance as $item) {
                if ($item['type'] === 'revenue') {
                    $eliminations[] = new EliminationEntry(
                        type: EliminationType::INTERCOMPANY_REVENUE,
                        debitAccountCode: $item['account'],
                        creditAccountCode: $this->getMatchingExpenseAccount($item['account']),
                        amount: $item['amount'],
                        description: 'Elimination of intercompany revenue'
                    );
                }
            }
        }

        return $eliminations;
    }

    public function appliesTo(array $consolidationData): bool
    {
        foreach ($consolidationData['balances'] ?? [] as $balance) {
            foreach ($balance as $item) {
                if ($item['type'] === 'revenue') {
                    return true;
                }
            }
        }
        return false;
    }

    private function getMatchingExpenseAccount(string $revenueAccount): string
    {
        // Logic to determine matching expense account
        return str_replace('4', '5', $revenueAccount);
    }
}
