<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

interface CircuitBreakerInterface
{
    /**
     * Check if the circuit is available (closed or half-open).
     *
     * @param string $serviceName The name of the service/gateway
     * @return bool True if available, false if open
     */
    public function isAvailable(string $serviceName): bool;

    /**
     * Record a successful execution.
     *
     * @param string $serviceName The name of the service/gateway
     */
    public function reportSuccess(string $serviceName): void;

    /**
     * Record a failed execution.
     *
     * @param string $serviceName The name of the service/gateway
     */
    public function reportFailure(string $serviceName): void;
}
