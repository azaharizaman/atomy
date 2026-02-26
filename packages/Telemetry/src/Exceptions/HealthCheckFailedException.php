<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Exceptions;

/**
 * HealthCheckFailedException
 *
 * Thrown when a critical health check fails and the system cannot recover.
 *
 * @package Nexus\Telemetry\Exceptions
 */
final class HealthCheckFailedException extends MonitoringException
{
    public static function forCheck(string $checkName, string $reason): self
    {
        return new self(
            sprintf('Health check "%s" failed: %s', $checkName, $reason)
        );
    }

    public static function timeout(string $checkName, int $timeout): self
    {
        return new self(
            sprintf('Health check "%s" timed out after %d seconds', $checkName, $timeout)
        );
    }
}
