<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Exceptions;

/**
 * Exception thrown when coordination between packages/services fails
 */
class CoordinationException extends \Exception
{
    /**
     * Create a new coordination exception
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable
     */
    public function __construct(
        string $message = 'Coordination failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for service unavailable
     */
    public static function serviceUnavailable(string $coordinatorName, string $serviceName): self
    {
        return new self(
            sprintf('Coordinator "%s" cannot reach service "%s"', $coordinatorName, $serviceName)
        );
    }

    /**
     * Create exception for data consistency error
     */
    public static function dataInconsistency(string $coordinatorName, string $details): self
    {
        return new self(
            sprintf('Data inconsistency detected in coordinator "%s": %s', $coordinatorName, $details)
        );
    }

    /**
     * Create exception for dependency failure
     */
    public static function dependencyFailed(string $coordinatorName, string $dependencyName, string $reason): self
    {
        return new self(
            sprintf(
                'Coordinator "%s" dependency "%s" failed: %s',
                $coordinatorName,
                $dependencyName,
                $reason
            )
        );
    }

    /**
     * Create exception for circular dependency
     */
    public static function circularDependency(string $coordinatorName, array $dependencyChain): self
    {
        return new self(
            sprintf(
                'Circular dependency detected in coordinator "%s": %s',
                $coordinatorName,
                implode(' -> ', $dependencyChain)
            )
        );
    }

    /**
     * Create exception for transaction rollback
     */
    public static function transactionRollback(string $coordinatorName, string $reason): self
    {
        return new self(
            sprintf('Transaction rolled back in coordinator "%s": %s', $coordinatorName, $reason)
        );
    }

    /**
     * Create exception for partial failure
     */
    public static function partialFailure(string $coordinatorName, array $failedOperations, array $successfulOperations): self
    {
        return new self(
            sprintf(
                'Partial failure in coordinator "%s": %d operations failed (%s), %d succeeded (%s)',
                $coordinatorName,
                count($failedOperations),
                implode(', ', $failedOperations),
                count($successfulOperations),
                implode(', ', $successfulOperations)
            )
        );
    }

    /**
     * Create exception for timeout during coordination
     */
    public static function timeout(string $coordinatorName, int $timeoutSeconds): self
    {
        return new self(
            sprintf('Coordination in "%s" timed out after %d seconds', $coordinatorName, $timeoutSeconds)
        );
    }

    /**
     * Create exception for invalid request
     */
    public static function invalidRequest(string $coordinatorName, string $reason): self
    {
        return new self(
            sprintf('Invalid request for coordinator "%s": %s', $coordinatorName, $reason)
        );
    }
}
