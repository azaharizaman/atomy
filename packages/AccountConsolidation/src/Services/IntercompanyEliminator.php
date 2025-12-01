<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Services;

use Nexus\AccountConsolidation\Contracts\EliminationRuleInterface;
use Nexus\AccountConsolidation\ValueObjects\EliminationEntry;
use Nexus\AccountConsolidation\ValueObjects\IntercompanyBalance;

/**
 * Pure logic for eliminating intercompany transactions.
 */
final readonly class IntercompanyEliminator
{
    /**
     * @param array<EliminationRuleInterface> $rules
     */
    public function __construct(
        private array $rules = [],
    ) {}

    /**
     * Generate elimination entries for intercompany balances.
     *
     * @param array<IntercompanyBalance> $balances
     * @return array<EliminationEntry>
     */
    public function eliminate(array $balances): array
    {
        $eliminations = [];
        $consolidationData = $this->prepareConsolidationData($balances);

        foreach ($this->rules as $rule) {
            if ($rule->appliesTo($consolidationData)) {
                $ruleEliminations = $rule->apply($consolidationData);
                $eliminations = array_merge($eliminations, $ruleEliminations);
            }
        }

        return $eliminations;
    }

    /**
     * @param array<IntercompanyBalance> $balances
     * @return array<string, mixed>
     */
    private function prepareConsolidationData(array $balances): array
    {
        $data = ['balances' => []];

        foreach ($balances as $balance) {
            $key = $balance->getFromEntityId() . '_' . $balance->getToEntityId();
            $data['balances'][$key][] = [
                'account' => $balance->getAccountCode(),
                'amount' => $balance->getAmount(),
                'type' => $balance->getTransactionType(),
            ];
        }

        return $data;
    }
}
