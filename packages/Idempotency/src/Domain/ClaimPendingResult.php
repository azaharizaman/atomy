<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Domain;

/**
 * Result of an atomic insert-if-absent (claim) on the idempotency store.
 */
final readonly class ClaimPendingResult
{
    public function __construct(
        public bool $claimedNew,
        public IdempotencyRecord $record,
    ) {
    }
}
