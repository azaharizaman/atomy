<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\AssignmentStrategyInterface;

final readonly class TerritoryBasedStrategy implements AssignmentStrategyInterface
{
    public function assign(array $leadData, array $assignees): ?string
    {
        $leadTerritory = $leadData['territory'] ?? $leadData['region'] ?? null;
        
        if ($leadTerritory === null) {
            return null;
        }

        foreach ($assignees as $assignee) {
            $assigneeTerritories = $assignee['territories'] ?? [];
            
            if (in_array($leadTerritory, $assigneeTerritories, true)) {
                return $assignee['id'];
            }
        }
        
        return null;
    }

    public function getName(): string
    {
        return 'territory_based';
    }
}
