<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Rules;

use Nexus\AccountConsolidation\Contracts\EliminationRuleInterface;
use Nexus\AccountConsolidation\Enums\EliminationType;
use Nexus\AccountConsolidation\ValueObjects\EliminationEntry;

/**
 * Elimination rule for investment in subsidiary against subsidiary equity.
 */
final readonly class InvestmentEliminationRule implements EliminationRuleInterface
{
    public function getId(): string
    {
        return 'investment_elimination';
    }

    public function getName(): string
    {
        return 'Investment in Subsidiary Elimination';
    }

    public function apply(array $consolidationData): array
    {
        $eliminations = [];

        if (isset($consolidationData['investment'])) {
            $eliminations[] = new EliminationEntry(
                type: EliminationType::INVESTMENT_ELIMINATION,
                debitAccountCode: '3000',
                creditAccountCode: '1600',
                amount: $consolidationData['investment']['amount'] ?? 0.0,
                description: 'Elimination of investment in subsidiary'
            );
        }

        return $eliminations;
    }

    public function appliesTo(array $consolidationData): bool
    {
        return isset($consolidationData['investment']);
    }
}
