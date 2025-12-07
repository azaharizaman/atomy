<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when a P2P workflow step completes.
 */
final readonly class P2PStepCompletedEvent
{
    /**
     * @param array<string, mixed> $output
     */
    public function __construct(
        public string $instanceId,
        public string $stepId,
        public string $stepName,
        public array $output,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
