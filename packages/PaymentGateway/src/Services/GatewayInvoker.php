<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\CircuitBreakerInterface;
use Nexus\PaymentGateway\Contracts\RetryStrategyInterface;
use Nexus\PaymentGateway\Exceptions\GatewayException;
use Throwable;

final class GatewayInvoker
{
    public function __construct(
        private readonly RetryStrategyInterface $retryStrategy,
        private readonly ?CircuitBreakerInterface $circuitBreaker = null
    ) {}

    /**
     * Execute a gateway operation with retry and circuit breaker logic.
     *
     * @template T
     * @param callable(): T $operation
     * @param string $serviceName Name of the service for circuit breaker
     * @return T
     * @throws Throwable
     */
    public function invoke(callable $operation, string $serviceName): mixed
    {
        if ($this->circuitBreaker && !$this->circuitBreaker->isAvailable($serviceName)) {
            throw new GatewayException("Circuit breaker is open for service: {$serviceName}");
        }

        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                $result = $operation();
                
                if ($this->circuitBreaker) {
                    $this->circuitBreaker->reportSuccess($serviceName);
                }

                return $result;
            } catch (Throwable $e) {
                if ($this->circuitBreaker) {
                    $this->circuitBreaker->reportFailure($serviceName);
                }

                if (!$this->retryStrategy->shouldRetry($attempts, $e)) {
                    throw $e;
                }

                $delay = $this->retryStrategy->getDelay($attempts);
                usleep($delay * 1000); // usleep takes microseconds
            }
        }
    }
}
