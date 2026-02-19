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
        
        $assigneeId = $assignees[$index]['id'] ?? null;
        
        $this->state->setIndex($key, ($index + 1) % count($assignees));
        
        return $assigneeId;
    }

    public function getName(): string
    {
        return 'round_robin';
    }
}
