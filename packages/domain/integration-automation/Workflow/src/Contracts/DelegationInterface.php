<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Contract for user delegation.
 *
 * Manages temporary delegation of tasks from one user to another.
 */
interface DelegationInterface
{
    /**
     * Get the delegation ID.
     */
    public function getId(): string;

    /**
     * Get the delegator user ID (who is delegating).
     */
    public function getDelegatorId(): string;

    /**
     * Get the delegatee user ID (who receives delegation).
     */
    public function getDelegateeId(): string;

    /**
     * Get delegation start date.
     */
    public function getStartsAt(): \DateTimeInterface;

    /**
     * Get delegation end date.
     */
    public function getEndsAt(): \DateTimeInterface;

    /**
     * Check if delegation is currently active.
     */
    public function isActive(): bool;

    /**
     * Get delegation chain depth.
     *
     * If delegatee has also delegated, this tracks the chain length.
     */
    public function getChainDepth(): int;
}
