<?php

declare(strict_types=1);

namespace Nexus\Workflow\Core;

use Nexus\Workflow\Contracts\{WorkflowInterface, ActivityInterface};

/**
 * Compensation/rollback engine.
 *
 * Executes compensation activities in reverse order.
 */
final class CompensationEngine
{
    /**
     * Execute compensation for failed workflow.
     *
     * @param ActivityInterface[] $activities
     * @param array<string, mixed> $executionResults
     */
    public function compensate(
        WorkflowInterface $workflow,
        array $activities,
        array $executionResults = []
    ): void {
        // Execute in reverse order
        $reversed = array_reverse($activities);
        
        foreach ($reversed as $activity) {
            try {
                $result = $executionResults[$activity->getName()] ?? [];
                $activity->compensate($workflow, [], $result);
            } catch (\Exception $e) {
                // Log compensation failure but continue with remaining compensations
                // In production, this should be logged properly
                continue;
            }
        }
    }
}
