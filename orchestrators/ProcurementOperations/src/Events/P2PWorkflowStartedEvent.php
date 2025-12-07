<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when P2P workflow starts.
 */
final readonly class P2PWorkflowStartedEvent
{
    public function __construct(
        public string $instanceId,
        public string $tenantId,
        public string $userId,
        public array $data,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
