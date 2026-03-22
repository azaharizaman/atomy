<?php

declare(strict_types=1);

namespace Nexus\Outbox\Contracts;

/**
 * Composite port for outbox persistence (enqueue, claim, save, query).
 * Prefer {@see OutboxQueryInterface} and/or {@see OutboxPersistInterface} when a consumer only needs one side.
 */
interface OutboxStoreInterface extends OutboxQueryInterface, OutboxPersistInterface
{
}
