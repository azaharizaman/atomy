<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Identity\Contracts\AuthContextInterface;
use Nexus\Identity\Contracts\UserInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Laravel implementation of authentication context
 * 
 * Bridges Laravel's auth system to the framework-agnostic contract.
 */
final class LaravelAuthContext implements AuthContextInterface
{
    public function getCurrentUserId(): ?string
    {
        $id = Auth::id();
        return $id !== null ? (string) $id : null;
    }

    public function isAuthenticated(): bool
    {
        return Auth::check();
    }

    public function getCurrentUser(): ?UserInterface
    {
        $user = Auth::user();
        
        if ($user === null) {
            return null;
        }

        // Assume Laravel's User model implements UserInterface
        return $user instanceof UserInterface ? $user : null;
    }
}
