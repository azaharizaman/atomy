<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\LoginAttempt;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserAuthenticatorInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\ValueObjects\Credentials;
use Nexus\Identity\ValueObjects\UserStatus;
use Nexus\Identity\Exceptions\InvalidCredentialsException;
use Nexus\Identity\Exceptions\AccountLockedException;
use Nexus\Identity\Exceptions\AccountInactiveException;

/**
 * Laravel user authenticator implementation
 */
final readonly class LaravelUserAuthenticator implements UserAuthenticatorInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $hasher
    ) {
    }

    public function authenticate(Credentials $credentials): UserInterface
    {
        // Find user by email
        $user = $this->userRepository->findByEmailOrNull($credentials->email);

        // Record failed attempt if user not found
        if (!$user) {
            $this->recordFailedAttempt(null, $credentials->email, 'User not found');
            throw new InvalidCredentialsException('Invalid email or password');
        }

        // Check if account can authenticate
        if (!$this->canAuthenticate($user)) {
            $this->recordFailedAttempt($user->getId(), $credentials->email, 'Account cannot authenticate');

            if ($user->isLocked()) {
                throw new AccountLockedException('Your account has been locked');
            }

            throw new AccountInactiveException($user->getStatus());
        }

        // Verify password
        if (!$this->hasher->verify($credentials->password, $user->getPasswordHash())) {
            $failedAttempts = $this->userRepository->incrementFailedLoginAttempts($user->getId());
            $this->recordFailedAttempt($user->getId(), $credentials->email, 'Invalid password');

            // Lock account after threshold
            $lockoutThreshold = config('identity.lockout.threshold', 5);
            if ($failedAttempts >= $lockoutThreshold) {
                $this->userRepository->lockAccount($user->getId(), 'Too many failed login attempts');
                throw new AccountLockedException('Account has been locked due to too many failed login attempts');
            }

            throw new InvalidCredentialsException('Invalid email or password');
        }

        // Reset failed attempts on successful login
        $this->userRepository->resetFailedLoginAttempts($user->getId());
        $this->recordSuccessfulAttempt($user->getId(), $credentials->email);

        return $user;
    }

    public function verifyCredentials(Credentials $credentials): bool
    {
        try {
            $this->authenticate($credentials);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isAccountLocked(string $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        return $user->isLocked();
    }

    public function canAuthenticate(UserInterface $user): bool
    {
        // Check if account is active
        return $user->isActive();
    }

    private function recordFailedAttempt(?string $userId, string $email, string $reason): void
    {
        LoginAttempt::create([
            'user_id' => $userId,
            'email' => $email,
            'successful' => false,
            'ip_address' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown',
            'failure_reason' => $reason,
        ]);
    }

    private function recordSuccessfulAttempt(string $userId, string $email): void
    {
        LoginAttempt::create([
            'user_id' => $userId,
            'email' => $email,
            'successful' => true,
            'ip_address' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown',
        ]);
    }
}
