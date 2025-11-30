<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\Credentials;

/**
 * User authenticator interface
 * 
 * Handles user credential verification
 */
interface UserAuthenticatorInterface
{
    /**
     * Authenticate a user with credentials
     * 
     * @param Credentials $credentials User credentials (email and password)
     * @return UserInterface Authenticated user
     * @throws \Nexus\Identity\Exceptions\InvalidCredentialsException
     * @throws \Nexus\Identity\Exceptions\AccountLockedException
     * @throws \Nexus\Identity\Exceptions\AccountInactiveException
     */
    public function authenticate(Credentials $credentials): UserInterface;

    /**
     * Verify user credentials without authentication side effects
     * 
     * @param Credentials $credentials User credentials
     * @return bool True if credentials are valid
     */
    public function verifyCredentials(Credentials $credentials): bool;

    /**
     * Check if a user account is locked
     */
    public function isAccountLocked(string $userId): bool;

    /**
     * Check if a user can authenticate (not locked, is active, email verified)
     */
    public function canAuthenticate(UserInterface $user): bool;
}
