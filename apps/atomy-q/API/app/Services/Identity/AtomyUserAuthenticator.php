<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User as UserModel;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\UserAuthenticatorInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Exceptions\AccountInactiveException;
use Nexus\Identity\Exceptions\AccountLockedException;
use Nexus\Identity\Exceptions\InvalidCredentialsException;
use Nexus\Identity\ValueObjects\Credentials;

/**
 * Password verification against `users`. Email may exist in multiple tenants; the matching row wins.
 */
final readonly class AtomyUserAuthenticator implements UserAuthenticatorInterface
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function authenticate(Credentials $credentials): UserInterface
    {
        $user = $this->resolveUserForPassword($credentials->email, $credentials->password);
        if (! $this->canAuthenticate($user)) {
            if ($user->isLocked()) {
                throw new AccountLockedException();
            }
            throw new AccountInactiveException($user->getStatus());
        }

        return $user;
    }

    public function verifyCredentials(Credentials $credentials): bool
    {
        try {
            $this->resolveUserForPassword($credentials->email, $credentials->password);
        } catch (InvalidCredentialsException) {
            return false;
        }

        return true;
    }

    public function isAccountLocked(string $userId): bool
    {
        $row = UserModel::query()->whereKey($userId)->first();

        return $row !== null && (string) $row->status === 'locked';
    }

    public function canAuthenticate(UserInterface $user): bool
    {
        return $user->isActive() && ! $user->isLocked();
    }

    private function resolveUserForPassword(string $email, string $password): UserInterface
    {
        $normalized = strtolower(trim($email));
        $candidates = UserModel::query()->where('email', $normalized)->get();
        foreach ($candidates as $row) {
            if ($this->passwordHasher->verify($password, (string) $row->password_hash)) {
                return new AtomyIdentityUser($row);
            }
        }

        throw new InvalidCredentialsException();
    }
}
