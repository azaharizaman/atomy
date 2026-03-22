<?php

declare(strict_types=1);

namespace Nexus\Outbox\Domain;

/**
 * Result of {@see \Nexus\Outbox\Contracts\OutboxPersistInterface::enqueue}.
 */
final readonly class EnqueueResult
{
    public function __construct(
        public bool $wasInserted,
        public OutboxRecord $record,
    ) {
    }
}
