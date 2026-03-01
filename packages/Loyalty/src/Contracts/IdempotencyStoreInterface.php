<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Contracts;

/**
 * Interface for idempotency token storage and verification.
 * Requirement: SEC-LOY-002
 */
interface IdempotencyStoreInterface
{
    /**
     * Check if a token exists in the store.
     */
    public function has(string $token): bool;

    /**
     * Mark a token as processed.
     */
    public function mark(string $token): void;
}
