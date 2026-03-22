<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Contracts;

/**
 * Composite persistence port: reads + writes + atomic claim.
 * Prefer depending on {@see IdempotencyQueryInterface} and/or {@see IdempotencyPersistInterface}
 * when a consumer only needs one side.
 */
interface IdempotencyStoreInterface extends IdempotencyQueryInterface, IdempotencyPersistInterface
{
}
