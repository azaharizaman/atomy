<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

/**
 * Base interface for all identity coordinators.
 */
interface CoordinatorInterface
{
    /**
     * Get the coordinator name.
     */
    public function getName(): string;

    /**
     * Check if the coordinator has all required data for a user.
     */
    public function hasRequiredData(string $userId): bool;
}
