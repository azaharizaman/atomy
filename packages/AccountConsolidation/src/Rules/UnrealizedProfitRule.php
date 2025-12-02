<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Rules;

use Nexus\AccountConsolidation\Contracts\EliminationRuleInterface;
use Nexus\AccountConsolidation\Enums\EliminationType;
use Nexus\AccountConsolidation\ValueObjects\EliminationEntry;

/**
 * Elimination rule for unrealized profit in inventory.
 */
final readonly class UnrealizedProfitRule implements EliminationRuleInterface
{
    public function getId(): string
    {
        return 'unrealized_profit';
    }

    public function getName(): string
    {
        return 'Unrealized Profit Elimination';
    }

    public function apply(array $consolidationData): array
    {
        $eliminations = [];

        if (isset($consolidationData['unrealized_profit'])) {
            $eliminations[] = new EliminationEntry(
                type: EliminationType::UNREALIZED_PROFIT,
                debitAccountCode: '5100',
                creditAccountCode: '1300',
                amount: $consolidationData['unrealized_profit']['amount'] ?? 0.0,
                description: 'Elimination of unrealized profit in inventory'
            );
        }

        return $eliminations;
    }

    public function appliesTo(array $consolidationData): bool
    {
        return isset($consolidationData['unrealized_profit']);
    }
}
