<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\GatewayProvider;

/**
 * Contract for idempotency key management.
 *
 * Ensures payment operations are not duplicated even when
 * retried due to network issues or timeouts.
 */
interface IdempotencyManagerInterface
{
    /**
     * Generate a new idempotency key.
     */
    public function generate(): string;

    /**
     * Check if an operation with this key has been processed.
     */
    public function hasBeenProcessed(
        GatewayProvider $provider,
        string $idempotencyKey,
    ): bool;

    /**
     * Get the stored result for a processed operation.
     *
     * @return array<string, mixed>|null
     */
    public function getStoredResult(
        GatewayProvider $provider,
        string $idempotencyKey,
    ): ?array;

    /**
     * Store the result of a processed operation.
     *
     * @param array<string, mixed> $result
     */
    public function storeResult(
        GatewayProvider $provider,
        string $idempotencyKey,
        array $result,
        int $ttlSeconds = 86400, // 24 hours default
    ): void;

    /**
     * Clear stored result (for cleanup).
     */
    public function clear(GatewayProvider $provider, string $idempotencyKey): void;

    /**
     * Execute an operation idempotently.
     *
     * @template T
     * @param GatewayProvider $provider
     * @param string $key
     * @param callable(): T $operation
     * @param class-string<T> $resultClass
     * @return T
     */
    public function execute(GatewayProvider $provider, string $key, callable $operation, string $resultClass): mixed;
}
