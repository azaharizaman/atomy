<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\AssignmentStrategyInterface;
use Nexus\CRMOperations\Contracts\RoundRobinStateInterface;

final readonly class RoundRobinStrategy implements AssignmentStrategyInterface
{
    public function __construct(
        private RoundRobinStateInterface $state
    ) {}

    public function assign(array $leadData, array $assignees): ?string
    {
        if (empty($assignees)) {
            return null;
        }

        $key = $leadData['tenant_id'] ?? 'default';
        
        $index = $this->state->getIndex($key);
        
        // Normalize index with modulo before selection to handle overflow
        $assigneeCount = count($assignees);
        $normalizedIndex = $index % $assigneeCount;
        
        $assigneeId = $assignees[$normalizedIndex]['id'] ?? null;
        
        $this->state->setIndex($key, ($index + 1) % $assigneeCount);
        
        return $assigneeId;
    }

    public function getName(): string
    {
        return 'round_robin';
    }
}
