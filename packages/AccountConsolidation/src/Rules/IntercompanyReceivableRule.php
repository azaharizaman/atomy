<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Rules;

use Nexus\AccountConsolidation\Contracts\EliminationRuleInterface;
use Nexus\AccountConsolidation\Enums\EliminationType;
use Nexus\AccountConsolidation\ValueObjects\EliminationEntry;

/**
 * Elimination rule for intercompany receivables/payables.
 */
final readonly class IntercompanyReceivableRule implements EliminationRuleInterface
{
    public function getId(): string
    {
        return 'intercompany_receivable';
    }

    public function getName(): string
    {
        return 'Intercompany Receivable/Payable Elimination';
    }

    public function apply(array $consolidationData): array
    {
        $eliminations = [];

        foreach ($consolidationData['balances'] ?? [] as $balance) {
            foreach ($balance as $item) {
                if ($item['type'] === 'receivable' || $item['type'] === 'payable') {
                    $eliminations[] = new EliminationEntry(
                        type: EliminationType::INTERCOMPANY_RECEIVABLE,
                        debitAccountCode: $this->getPayableAccount($item['account']),
                        creditAccountCode: $item['account'],
                        amount: $item['amount'],
                        description: 'Elimination of intercompany receivable/payable'
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
                if ($item['type'] === 'receivable' || $item['type'] === 'payable') {
                    return true;
                }
            }
        }
        return false;
    }

    private function getPayableAccount(string $receivableAccount): string
    {
        return str_replace('1', '2', $receivableAccount);
    }
}
