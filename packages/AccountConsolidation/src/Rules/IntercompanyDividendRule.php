<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Rules;

use Nexus\AccountConsolidation\Contracts\EliminationRuleInterface;
use Nexus\AccountConsolidation\Enums\EliminationType;
use Nexus\AccountConsolidation\ValueObjects\EliminationEntry;

/**
 * Elimination rule for intercompany dividends.
 */
final readonly class IntercompanyDividendRule implements EliminationRuleInterface
{
    public function getId(): string
    {
        return 'intercompany_dividend';
    }

    public function getName(): string
    {
        return 'Intercompany Dividend Elimination';
    }

    public function apply(array $consolidationData): array
    {
        $eliminations = [];

        foreach ($consolidationData['balances'] ?? [] as $balance) {
            foreach ($balance as $item) {
                if ($item['type'] === 'dividend') {
                    $eliminations[] = new EliminationEntry(
                        type: EliminationType::INTERCOMPANY_DIVIDEND,
                        debitAccountCode: '7100',
                        creditAccountCode: '3200',
                        amount: $item['amount'],
                        description: 'Elimination of intercompany dividend'
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
                if ($item['type'] === 'dividend') {
                    return true;
                }
            }
        }
        return false;
    }
}
