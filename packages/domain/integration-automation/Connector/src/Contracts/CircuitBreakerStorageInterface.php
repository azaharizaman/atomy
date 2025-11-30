<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

use Nexus\Connector\ValueObjects\CircuitBreakerState;

/**
 * Contract for circuit breaker state persistence.
 *
 * This interface must be implemented by the application layer
 * using Redis, database, or other shared storage mechanism.
 * 
 * CRITICAL: Circuit breaker state MUST be shared across all workers/processes
 * to prevent cascading failures in distributed environments.
 */
interface CircuitBreakerStorageInterface
{
    /**
     * Retrieve circuit breaker state for a service.
     *
     * @param string $serviceName Service identifier
     * @return CircuitBreakerState Circuit breaker state
     */
    public function getState(string $serviceName): CircuitBreakerState;

    /**
     * Store circuit breaker state for a service.
     *
     * @param string $serviceName Service identifier
     * @param CircuitBreakerState $state Circuit breaker state
     */
    public function setState(string $serviceName, CircuitBreakerState $state): void;

    /**
     * Check if circuit breaker exists for a service.
     *
     * @param string $serviceName Service identifier
     * @return bool True if state exists
     */
    public function hasState(string $serviceName): bool;

    /**
     * Reset circuit breaker state for a service.
     *
     * @param string $serviceName Service identifier
     */
    public function resetState(string $serviceName): void;

    /**
     * Clean up expired circuit breaker states.
     *
     * @return int Number of states cleaned up
     */
    public function cleanExpired(): int;
}
