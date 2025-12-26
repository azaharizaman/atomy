<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Throwable;

interface RetryStrategyInterface
{
    /**
     * Determine if the operation should be retried.
     *
     * @param int $attempts Current attempt count (1-based)
     * @param Throwable $exception The exception that caused the failure
     * @return bool True if should retry, false otherwise
     */
    public function shouldRetry(int $attempts, Throwable $exception): bool;

    /**
     * Get the delay in milliseconds before the next retry.
     *
     * @param int $attempts Current attempt count (1-based)
     * @return int Delay in milliseconds
     */
    public function getDelay(int $attempts): int;
}
