<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for coordination failures between packages.
 */
class CoordinationException extends ProcurementOperationsException
{
    /**
     * Create exception for service unavailable.
     */
    public static function serviceUnavailable(string $serviceName): self
    {
        return new self(
            sprintf('Service unavailable: %s', $serviceName)
        );
    }

    /**
     * Create exception for package integration failure.
     */
    public static function integrationFailed(string $packageName, string $operation, string $reason): self
    {
        return new self(
            sprintf(
                'Integration with %s failed during %s: %s',
                $packageName,
                $operation,
                $reason
            )
        );
    }

    /**
     * Create exception for rollback required.
     */
    public static function rollbackRequired(string $operation, string $reason): self
    {
        return new self(
            sprintf('Operation %s requires rollback: %s', $operation, $reason)
        );
    }

    /**
     * Create exception for partial completion.
     *
     * @param array<string, bool> $stepResults
     */
    public static function partialCompletion(string $operation, array $stepResults): self
    {
        $completed = array_keys(array_filter($stepResults));
        $failed = array_keys(array_filter($stepResults, fn($v) => !$v));

        return new self(
            sprintf(
                'Operation %s partially completed. Completed: [%s], Failed: [%s]',
                $operation,
                implode(', ', $completed),
                implode(', ', $failed)
            )
        );
    }
}
