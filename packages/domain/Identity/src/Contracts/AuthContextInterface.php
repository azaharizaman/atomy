<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

use Nexus\Domain\Identity\Contracts\UserInterface;
/**
 * Authentication context interface
 * 
 * Provides access to the currently authenticated user in the application context.
 */
interface AuthContextInterface
{
    /**
     * Get the ID of the currently authenticated user
     * 
     * @return string|null User ID or null if not authenticated
     */
    public function getCurrentUserId(): ?string;

    /**
     * Check if a user is currently authenticated
     * 
     * @return bool True if a user is authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Get the currently authenticated user
     * 
     * @return UserInterface|null User instance or null if not authenticated
     */
    public function getCurrentUser(): ?UserInterface;
}
