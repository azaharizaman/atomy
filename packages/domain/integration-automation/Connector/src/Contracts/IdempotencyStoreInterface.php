<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

use Nexus\Connector\ValueObjects\IdempotencyKey;

/**
 * Contract for idempotency key storage.
 *
 * This interface must be implemented by the application layer
 * to store and retrieve idempotency keys with their response data.
 */
interface IdempotencyStoreInterface
{
    /**
     * Store an idempotency key with its response.
     *
     * @param IdempotencyKey $key Idempotency key
     * @param array<string, mixed> $response Response data to cache
     * @param string $serviceName Service name for namespacing
     */
    public function store(IdempotencyKey $key, array $response, string $serviceName): void;

    /**
     * Retrieve cached response for an idempotency key.
     *
     * @param IdempotencyKey $key Idempotency key
     * @param string $serviceName Service name for namespacing
     * @return array<string, mixed>|null Cached response or null if not found
     */
    public function retrieve(IdempotencyKey $key, string $serviceName): ?array;

    /**
     * Check if an idempotency key exists.
     *
     * @param IdempotencyKey $key Idempotency key
     * @param string $serviceName Service name for namespacing
     */
    public function exists(IdempotencyKey $key, string $serviceName): bool;

    /**
     * Remove an idempotency key from storage.
     *
     * @param IdempotencyKey $key Idempotency key
     * @param string $serviceName Service name for namespacing
     */
    public function forget(IdempotencyKey $key, string $serviceName): void;

    /**
     * Clean up expired idempotency keys.
     */
    public function cleanExpired(): int;
}
