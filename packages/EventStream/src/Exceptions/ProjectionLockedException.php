<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * Projection Locked Exception
 *
 * Thrown when attempting to rebuild a projection that is already locked.
 *
 * @package Nexus\EventStream\Exceptions
 */
final class ProjectionLockedException extends EventStreamException
{
    private function __construct(
        string $message,
        private readonly string $projectorName,
        private readonly int $lockAgeSeconds
    ) {
        parent::__construct($message);
    }

    /**
     * Create exception for locked projection.
     *
     * @param string $projectorName Projector name
     * @param int $lockAgeSeconds Age of the lock in seconds
     * @return self
     */
    public static function alreadyLocked(string $projectorName, int $lockAgeSeconds): self
    {
        return new self(
            "Projection '{$projectorName}' is already locked (age: {$lockAgeSeconds}s). " .
            "Another process may be rebuilding it.",
            $projectorName,
            $lockAgeSeconds
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
     * Get the lock age in seconds.
     *
     * @return int
     */
    public function getLockAgeSeconds(): int
    {
        return $this->lockAgeSeconds;
    }

    /**
     * Check if lock is likely a zombie (stale lock from crashed process).
     *
     * @param int $zombieThresholdSeconds Threshold in seconds (default: 3600 = 1 hour)
     * @return bool
     */
    public function isLikelyZombie(int $zombieThresholdSeconds = 3600): bool
    {
        return $this->lockAgeSeconds > $zombieThresholdSeconds;
    }
}
