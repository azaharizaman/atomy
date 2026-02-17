<?php

declare(strict_types=1);

namespace App\Service;

use Nexus\Identity\Contracts\AuthContextInterface;
use Nexus\Identity\Contracts\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Authentication context implementation for atomy-api.
 *
 * Wraps Symfony Security to provide Nexus Identity context.
 */
final readonly class AuthContext implements AuthContextInterface
{
    public function __construct(
        private Security $security
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUserId(): ?string
    {
        $user = $this->security->getUser();
        
        if ($user instanceof UserInterface) {
            return $user->getId();
        }

        // Handle cases where user might be a different class (e.g. from a different provider)
        if ($user !== null && method_exists($user, 'getId')) {
            return (string) $user->getId();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool
    {
        return $this->security->getUser() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUser(): ?UserInterface
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user : null;
    }
}
