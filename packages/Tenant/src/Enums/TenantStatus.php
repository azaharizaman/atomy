<?php

declare(strict_types=1);

namespace Nexus\Tenant\Enums;

/**
 * Tenant Status Enum
 *
 * Native PHP enum representing tenant lifecycle statuses.
 * Defines valid states and allowed transitions between states.
 *
 * @package Nexus\Tenant\Enums
 * @see https://www.php.net/manual/en/language.enumerations.backed.php
 */
enum TenantStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
    case Trial = 'trial';

    /**
     * Check if tenant is in pending state
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Check if tenant is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if tenant is suspended
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this === self::Suspended;
    }

    /**
     * Check if tenant is archived
     *
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this === self::Archived;
    }

    /**
     * Check if tenant is in trial mode
     *
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this === self::Trial;
    }

    /**
     * Check if transition to a new status is allowed
     *
     * @param TenantStatus $newStatus Target status to transition to
     * @return bool True if transition is allowed, false otherwise
     */
    public function canTransitionTo(self $newStatus): bool
    {
        $transitions = [
            self::Pending->value => [self::Active->value, self::Archived->value, self::Trial->value],
            self::Active->value => [self::Suspended->value, self::Archived->value],
            self::Suspended->value => [self::Active->value, self::Archived->value],
            self::Trial->value => [self::Active->value, self::Suspended->value, self::Archived->value],
            self::Archived->value => [], // Cannot transition from archived
        ];

        return in_array($newStatus->value, $transitions[$this->value] ?? [], true);
    }

    /**
     * Check if this status equals another status
     *
     * @param TenantStatus $other Status to compare with
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this === $other;
    }
}
