<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\GatewayStatus;

/**
 * Contract for gateway health monitoring.
 *
 * Tracks gateway availability, latency, and error rates
 * for circuit breaker and failover decisions.
 */
interface GatewayHealthInterface
{
    /**
     * Get current gateway status.
     */
    public function getStatus(GatewayProvider $provider): GatewayStatus;

    /**
     * Record a successful operation.
     */
    public function recordSuccess(GatewayProvider $provider, float $latencyMs): void;

    /**
     * Record a failed operation.
     */
    public function recordFailure(GatewayProvider $provider, string $errorCode): void;

    /**
     * Get average latency for a provider.
     */
    public function getAverageLatency(GatewayProvider $provider): float;

    /**
     * Get error rate for a provider (0.0 - 1.0).
     */
    public function getErrorRate(GatewayProvider $provider): float;

    /**
     * Check if gateway is healthy enough for transactions.
     */
    public function isHealthy(GatewayProvider $provider): bool;

    /**
     * Reset health statistics for a provider.
     */
    public function reset(GatewayProvider $provider): void;
}
