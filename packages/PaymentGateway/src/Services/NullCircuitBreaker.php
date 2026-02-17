<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\CircuitBreakerInterface;

/**
 * A null implementation of the Circuit Breaker pattern.
 *
 * This implementation always allows execution and does not track failures.
 * Useful for testing or when circuit breaking is not required.
 */
final class NullCircuitBreaker implements CircuitBreakerInterface
{
    public function isAvailable(string $serviceName): bool
    {
        return true;
    }

    public function reportSuccess(string $serviceName): void
    {
        // No-op
    }

    public function reportFailure(string $serviceName): void
    {
        // No-op
    }
}
