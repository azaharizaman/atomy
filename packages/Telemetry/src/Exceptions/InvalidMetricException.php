<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Exceptions;

/**
 * InvalidMetricException
 *
 * Thrown when attempting to record an invalid metric.
 *
 * @package Nexus\Telemetry\Exceptions
 */
final class InvalidMetricException extends MonitoringException
{
    public static function invalidName(string $name): self
    {
        return new self(
            sprintf('Invalid metric name "%s". Must match pattern: [a-z][a-z0-9_]*', $name)
        );
    }

    public static function invalidValue(string $metricName, mixed $value): self
    {
        $type = get_debug_type($value);
        return new self(
            sprintf('Invalid value type for metric "%s": expected float, got %s', $metricName, $type)
        );
    }

    public static function invalidTags(string $metricName, string $reason): self
    {
        return new self(
            sprintf('Invalid tags for metric "%s": %s', $metricName, $reason)
        );
    }
}
