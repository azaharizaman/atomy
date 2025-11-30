<?php

declare(strict_types=1);

namespace Nexus\Workflow\Services;

use Nexus\Workflow\Contracts\{
    WorkflowInterface,
    WorkflowRepositoryInterface,
    TimerRepositoryInterface
};
use Nexus\Workflow\ValueObjects\SlaStatus;

/**
 * SLA Service - SLA tracking and monitoring.
 *
 * Public API for SLA operations.
 */
final readonly class SlaService
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
        private TimerRepositoryInterface $timerRepository
    ) {}

    /**
     * Get SLA status for a workflow.
     */
    public function getSlaStatus(string $workflowId): SlaStatus
    {
        $workflow = $this->workflowRepository->findById($workflowId);
        
        // Calculate SLA status based on workflow data and timers
        // This is a placeholder - actual implementation depends on configuration
        
        return SlaStatus::ON_TRACK;
    }

    /**
     * Get breached workflows.
     *
     * @return WorkflowInterface[]
     */
    public function getBreaches(): array
    {
        // Query workflows with breached SLA
        throw new \RuntimeException('Implementation required in repository layer');
    }

    /**
     * Track SLA for a workflow.
     *
     * Creates timers for SLA checks.
     */
    public function trackSla(string $workflowId, string $duration): void
    {
        // Create SLA check timer
        // Implementation depends on timer repository
    }
}
