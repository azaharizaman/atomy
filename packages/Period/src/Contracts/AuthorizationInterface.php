<?php

declare(strict_types=1);

namespace Nexus\Period\Contracts;

/**
 * Authorization Interface
 * 
 * Contract for authorization checks needed by Period package.
 * Implementation must be provided in the application layer.
 */
interface AuthorizationInterface
{
    /**
     * Check if a user can reopen a period
     * 
     * @param string $userId The user identifier
     * @return bool True if authorized
     */
    public function canReopenPeriod(string $userId): bool;

    /**
     * Check if a user can close a period
     * 
     * @param string $userId The user identifier
     * @return bool True if authorized
     */
    public function canClosePeriod(string $userId): bool;

    /**
     * Check if a user can lock a period
     * 
     * @param string $userId The user identifier
     * @return bool True if authorized
     */
    public function canLockPeriod(string $userId): bool;

    /**
     * Check if a user can create periods
     * 
     * @param string $userId The user identifier
     * @return bool True if authorized
     */
    public function canCreatePeriod(string $userId): bool;

    /**
     * Check if a user can delete periods
     * 
     * @param string $userId The user identifier
     * @return bool True if authorized
     */
    public function canDeletePeriod(string $userId): bool;

    /**
     * Check if a user can unlock a locked period
     * 
     * This is a highly restricted permission that should only be granted
     * to senior financial controllers or system administrators.
     * 
     * @param string $userId The user identifier
     * @return bool True if authorized
     */
    public function canUnlockPeriod(string $userId): bool;
}
