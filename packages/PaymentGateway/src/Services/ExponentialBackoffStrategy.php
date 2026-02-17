<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\RetryStrategyInterface;
use Nexus\PaymentGateway\Exceptions\NetworkException;
use Nexus\PaymentGateway\Exceptions\RateLimitException;
use Throwable;

final class ExponentialBackoffStrategy implements RetryStrategyInterface
{
    public function __construct(
        private readonly int $maxAttempts = 3,
        private readonly int $baseDelayMs = 100,
        private readonly int $maxDelayMs = 5000,
        private readonly float $multiplier = 2.0
    ) {}

    public function shouldRetry(int $attempts, Throwable $exception): bool
    {
        if ($attempts >= $this->maxAttempts) {
            return false;
        }

        // Retry on NetworkException and RateLimitException
        if ($exception instanceof NetworkException || $exception instanceof RateLimitException) {
            return true;
        }

        // Also retry on generic connection timeouts if not using custom exceptions yet
        // (This is a fallback, ideally we use the custom exceptions)
        if ($this->isNetworkError($exception)) {
            return true;
        }

        return false;
    }

    public function getDelay(int $attempts): int
    {
        $delay = $this->baseDelayMs * ($this->multiplier ** ($attempts - 1));
        
        // Add jitter (randomness) to prevent thundering herd
        $jitter = mt_rand(0, (int)($delay * 0.1));
        
        return min($this->maxDelayMs, (int)($delay + $jitter));
    }

    private function isNetworkError(Throwable $e): bool
    {
        // Basic check for common network related messages if not typed
        $message = strtolower($e->getMessage());
        return str_contains($message, 'timeout')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'network is unreachable');
    }
}
