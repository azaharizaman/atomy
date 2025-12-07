<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when P2P workflow completes successfully.
 */
final readonly class P2PWorkflowCompletedEvent
{
    /**
     * @param array<string> $completedSteps
     * @param array<string, mixed> $outputs
     */
    public function __construct(
        public string $instanceId,
        public string $tenantId,
        public array $completedSteps,
        public array $outputs,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
