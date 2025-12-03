<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Listeners;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener: Trigger onboarding workflow when employee is hired.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Listeners react to events
 * - Async side-effects
 * - Triggers workflows/coordinators
 */
final readonly class TriggerOnboardingOnHired
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the EmployeeHiredEvent.
     */
    public function handle(array $event): void
    {
        $this->logger->info('Employee hired event received, triggering onboarding', [
            'employee_id' => $event['employeeId'] ?? null,
            'user_id' => $event['userId'] ?? null,
        ]);

        // Implementation: Trigger OnboardingWorkflow
        // This would typically dispatch a job or call workflow service
    }
}
