<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

/**
 * Authentication context for reporting operations.
 */
interface AuthContextInterface
{
    /**
     * Get the ID of the currently authenticated user.
     * 
     * @return string|null
     */
    public function getUserId(): ?string;

    /**
     * Check if a user is currently authenticated.
     * 
     * @return bool
     */
    public function isAuthenticated(): bool;
}
