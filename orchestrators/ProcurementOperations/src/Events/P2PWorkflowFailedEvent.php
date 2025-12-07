<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when P2P workflow fails.
 */
final readonly class P2PWorkflowFailedEvent
{
    /**
     * @param array<string> $compensatedSteps
     */
    public function __construct(
        public string $instanceId,
        public string $tenantId,
        public ?string $failedStep,
        public string $error,
        public array $compensatedSteps,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
