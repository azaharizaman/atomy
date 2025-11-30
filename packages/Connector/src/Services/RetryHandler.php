<?php

declare(strict_types=1);

namespace Nexus\Connector\Services;

use Nexus\Connector\Exceptions\ConnectionException;
use Nexus\Connector\ValueObjects\RetryPolicy;

/**
 * Handles retry logic with exponential backoff.
 */
final class RetryHandler
{
    /**
     * Execute a callback with retry logic.
     *
     * @template T
     * @param callable(int): T $callback Function to execute (receives attempt number)
     * @param RetryPolicy $retryPolicy Retry configuration
     * @return T Result from successful callback execution
     * @throws ConnectionException If all retry attempts fail
     */
    public function execute(callable $callback, RetryPolicy $retryPolicy): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $retryPolicy->maxAttempts; $attempt++) {
            try {
                return $callback($attempt);
            } catch (\Throwable $e) {
                $lastException = $e;

                // Don't retry if we've exhausted attempts
                if ($attempt >= $retryPolicy->maxAttempts) {
                    break;
                }

                // Check if exception is retryable
                if ($e instanceof ConnectionException && $e->httpStatusCode !== null) {
                    if (!$retryPolicy->isRetryable($e->httpStatusCode)) {
                        break;
                    }
                }

                // Calculate and apply backoff delay
                $delayMs = $retryPolicy->calculateDelay($attempt);
                
                if ($delayMs > 0) {
                    usleep($delayMs * 1000); // Convert ms to microseconds
                }
            }
        }

        // All attempts failed
        throw $lastException ?? new \RuntimeException('Retry failed with no exception');
    }

    /**
     * Execute with a simple retry count (no policy).
     *
     * @template T
     * @param callable(): T $callback
     * @param int $maxAttempts
     * @return T
     */
    public function executeSimple(callable $callback, int $maxAttempts = 3): mixed
    {
        return $this->execute(
            callback: fn() => $callback(),
            retryPolicy: new RetryPolicy(maxAttempts: $maxAttempts)
        );
    }
}
