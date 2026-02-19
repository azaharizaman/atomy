<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Coordinators;

use Nexus\CRMOperations\Contracts\RoutingRuleInterface;
use Nexus\CRMOperations\Contracts\AssignmentStrategyInterface;
use Nexus\CRMOperations\Contracts\RoundRobinStateInterface;
use Nexus\CRMOperations\Services\RoundRobinState;
use Nexus\CRMOperations\Services\RoundRobinStrategy;
use Nexus\CRMOperations\Services\TerritoryBasedStrategy;
use Nexus\CRMOperations\Services\SkillBasedStrategy;
use Psr\Log\LoggerInterface;

final readonly class LeadRoutingCoordinator
{
    private RoundRobinStateInterface $roundRobinState;

    public function __construct(
        private ?LoggerInterface $logger = null,
        ?RoundRobinStateInterface $roundRobinState = null
    ) {
        $this->roundRobinState = $roundRobinState ?? new RoundRobinState();
    }

    public function routeLead(array $leadData, array $assignees, string $strategy = 'round_robin'): ?string
    {
        $strategyObj = $this->getStrategy($strategy);
        
        $assigneeId = $strategyObj->assign($leadData, $assignees);
        
        $this->logger?->info('Lead routed', [
            'lead_id' => $leadData['id'] ?? 'unknown',
            'assignee_id' => $assigneeId,
            'strategy' => $strategy
        ]);
        
        return $assigneeId;
    }

    public function routeLeadsBatch(array $leads, array $assignees, string $strategy = 'round_robin'): array
    {
        $results = [];
        
        foreach ($leads as $lead) {
            $results[$lead['id']] = $this->routeLead($lead, $assignees, $strategy);
        }
        
        return $results;
    }

    public function evaluateRoutingRules(array $leadData, array $rules): ?string
    {
        usort($rules, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
        
        foreach ($rules as $rule) {
            if ($rule->evaluate($leadData)) {
                return $rule->getName();
            }
        }
        
        return null;
    }

    private function getStrategy(string $strategy): AssignmentStrategyInterface
    {
        return match ($strategy) {
            'round_robin' => new RoundRobinStrategy($this->roundRobinState),
            'territory_based' => new TerritoryBasedStrategy(),
            'skill_based' => new SkillBasedStrategy(),
            default => new RoundRobinStrategy($this->roundRobinState),
        };
    }
}
