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
    private const MAX_FAILED_LOGIN_ATTEMPTS = 5;

    public function __construct(
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function authenticate(Credentials $credentials): UserInterface
    {
        $normalized = $this->normalizeEmail($credentials->email);
        $row = $this->resolveCandidateRow($normalized);
        if ($row === null) {
            throw new InvalidCredentialsException();
        }

        if ($this->rowIsLocked($row)) {
            throw new AccountLockedException();
        }

        if (! $this->passwordHasher->verify($credentials->password, (string) $row->password_hash)) {
            $attempts = $this->incrementFailedAttempts($row);

            if ($attempts >= self::MAX_FAILED_LOGIN_ATTEMPTS) {
                $this->lockAccount($row, 'too_many_failed_login_attempts');
                throw new AccountLockedException();
            }

            throw new InvalidCredentialsException();
        }

        // Successful login: clear counters and any temporary lock metadata.
        $this->resetFailedAttempts($row);

        $user = new AtomyIdentityUser($row->fresh() ?? $row);
        if (! $this->canAuthenticate($user)) {
            if ($this->isAccountLocked($user->getId())) {
                throw new AccountLockedException();
            }
            throw new AccountInactiveException($user->getStatus());
        }

        return $user;
    }

    public function verifyCredentials(Credentials $credentials): bool
    {
        $row = $this->resolveCandidateRow($this->normalizeEmail($credentials->email));
        if ($row === null) {
            return false;
        }

        if ($this->rowIsLocked($row) || ! $this->canAuthenticate(new AtomyIdentityUser($row))) {
            return false;
        }

        return $this->passwordHasher->verify($credentials->password, (string) $row->password_hash);
    }

    public function isAccountLocked(string $userId): bool
    {
        $row = UserModel::query()->whereKey($userId)->first();

        return $row !== null && $this->rowIsLocked($row);
    }

    public function canAuthenticate(UserInterface $user): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        // `AtomyIdentityUser` does not currently account for timed lockout; enforce against the DB row.
        return ! $this->isAccountLocked($user->getId());
    }

    private function resolveCandidateRow(string $normalizedEmail): ?UserModel
    {
        return UserModel::query()
            ->where('email', $normalizedEmail)
            ->orderBy('tenant_id')
            ->first();
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function rowIsLocked(UserModel $row): bool
    {
        if ((string) $row->status === 'locked') {
            // Allow timed unlock if lockout_expires_at has passed.
            if ($row->lockout_expires_at !== null && $row->lockout_expires_at->isPast()) {
                $row->status = 'active';
                $row->lockout_reason = null;
                $row->lockout_expires_at = null;
                $row->failed_login_attempts = 0;
                $row->save();

                return false;
            }

            return true;
        }

        return $row->lockout_expires_at !== null && $row->lockout_expires_at->isFuture();
    }

    private function incrementFailedAttempts(UserModel $row): int
    {
        $row->failed_login_attempts = (int) ($row->failed_login_attempts ?? 0) + 1;
        $row->save();

        return (int) $row->failed_login_attempts;
    }

    private function resetFailedAttempts(UserModel $row): void
    {
        $row->failed_login_attempts = 0;
        $row->lockout_reason = null;
        $row->lockout_expires_at = null;
        $row->save();
    }

    private function lockAccount(UserModel $row, string $reason): void
    {
        $row->status = 'locked';
        $row->lockout_reason = trim($reason) !== '' ? $reason : null;
        $row->lockout_expires_at = null;
        $row->save();
    }
}
