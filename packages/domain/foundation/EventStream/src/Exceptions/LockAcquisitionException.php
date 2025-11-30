<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * Lock Acquisition Exception
 *
 * Thrown when projection lock cannot be acquired due to infrastructure issues.
 *
 * @package Nexus\EventStream\Exceptions
 */
final class LockAcquisitionException extends EventStreamException
{
    private function __construct(
        string $message,
        private readonly string $projectorName,
        private readonly string $reason
    ) {
        parent::__construct($message);
    }

    /**
     * Create exception for connection failure.
     *
     * @param string $projectorName Projector name
     * @param string $driver Lock driver name (e.g., 'redis', 'database')
     * @return self
     */
    public static function connectionFailed(string $projectorName, string $driver): self
    {
        return new self(
            "Failed to connect to lock driver '{$driver}' for projector '{$projectorName}'",
            $projectorName,
            'connection_failed'
        );
    }

    /**
     * Create exception for lock driver unavailable.
     *
     * @param string $projectorName Projector name
     * @param string $driver Lock driver name
     * @return self
     */
    public static function driverUnavailable(string $projectorName, string $driver): self
    {
        return new self(
            "Lock driver '{$driver}' is unavailable for projector '{$projectorName}'",
            $projectorName,
            'driver_unavailable'
        );
    }

    /**
     * Get the projector name.
     *
     * @return string
     */
    public function getProjectorName(): string
    {
        return $this->projectorName;
    }

    /**
     * Get the failure reason.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
