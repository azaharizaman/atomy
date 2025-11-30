<?php

declare(strict_types=1);

namespace Nexus\Identity\Services;

use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\PasswordValidatorInterface;
use Nexus\Identity\Exceptions\DuplicateEmailException;
use Nexus\Identity\Exceptions\PasswordValidationException;
use Nexus\Identity\Exceptions\UserNotFoundException;
use Nexus\Identity\ValueObjects\UserStatus;

/**
 * User manager service
 * 
 * Handles user lifecycle operations
 */
final readonly class UserManager
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private PasswordValidatorInterface $passwordValidator
    ) {
    }

    /**
     * Create a new user
     * 
     * @param array{email: string, password: string, name?: string, status?: string, tenant_id?: string} $data
     * @throws DuplicateEmailException
     * @throws PasswordValidationException
     */
    public function createUser(array $data): UserInterface
    {
        // Validate email uniqueness
        if ($this->userRepository->emailExists($data['email'])) {
            throw new DuplicateEmailException($data['email']);
        }

        // Validate password
        $errors = $this->passwordValidator->validate($data['password']);
        if (!empty($errors)) {
            throw new PasswordValidationException($errors);
        }

        // Hash password
        $data['password_hash'] = $this->passwordHasher->hash($data['password']);
        unset($data['password']);

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = UserStatus::PENDING_ACTIVATION->value;
        }

        return $this->userRepository->create($data);
    }

    /**
     * Update user information
     * 
     * @param string $userId User identifier
     * @param array<string, mixed> $data Updated user data
     * @throws UserNotFoundException
     * @throws DuplicateEmailException
     */
    public function updateUser(string $userId, array $data): UserInterface
    {
        // Check if user exists
        $user = $this->userRepository->findById($userId);

        // Validate email uniqueness if email is being changed
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            if ($this->userRepository->emailExists($data['email'], $userId)) {
                throw new DuplicateEmailException($data['email']);
            }
        }

        // Don't allow password updates through this method
        unset($data['password'], $data['password_hash']);

        return $this->userRepository->update($userId, $data);
    }

    /**
     * Change user password
     * 
     * @throws UserNotFoundException
     * @throws PasswordValidationException
     */
    public function changePassword(string $userId, string $newPassword): void
    {
        $user = $this->userRepository->findById($userId);

        // Validate new password
        $errors = $this->passwordValidator->validate($newPassword, $user);
        if (!empty($errors)) {
            throw new PasswordValidationException($errors);
        }

        // Hash and update password
        $passwordHash = $this->passwordHasher->hash($newPassword);
        $this->userRepository->update($userId, [
            'password_hash' => $passwordHash,
            'password_changed_at' => new \DateTimeImmutable(),
        ]);

        // Reset failed login attempts
        $this->userRepository->resetFailedLoginAttempts($userId);
    }

    /**
     * Activate a user account
     * 
     * @throws UserNotFoundException
     */
    public function activateUser(string $userId): void
    {
        $this->userRepository->update($userId, [
            'status' => UserStatus::ACTIVE->value,
            'email_verified_at' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * Deactivate a user account
     * 
     * @throws UserNotFoundException
     */
    public function deactivateUser(string $userId): void
    {
        $this->userRepository->update($userId, [
            'status' => UserStatus::INACTIVE->value,
        ]);
    }

    /**
     * Suspend a user account
     * 
     * @throws UserNotFoundException
     */
    public function suspendUser(string $userId, string $reason): void
    {
        $user = $this->userRepository->findById($userId);
        $metadata = $user->getMetadata() ?? [];
        $metadata['suspension_reason'] = $reason;
        $metadata['suspended_at'] = (new \DateTimeImmutable())->format('c');
        
        $this->userRepository->update($userId, [
            'status' => UserStatus::SUSPENDED->value,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Lock a user account
     * 
     * @throws UserNotFoundException
     */
    public function lockUser(string $userId, string $reason): void
    {
        $this->userRepository->lockAccount($userId, $reason);
    }

    /**
     * Unlock a user account
     * 
     * @throws UserNotFoundException
     */
    public function unlockUser(string $userId): void
    {
        $this->userRepository->unlockAccount($userId);
        $this->userRepository->resetFailedLoginAttempts($userId);
    }

    /**
     * Delete a user account
     * 
     * @throws UserNotFoundException
     */
    public function deleteUser(string $userId): void
    {
        $this->userRepository->delete($userId);
    }

    /**
     * Assign a role to a user
     * 
     * @throws UserNotFoundException
     */
    public function assignRole(string $userId, string $roleId): void
    {
        $this->userRepository->findById($userId); // Verify user exists
        $this->userRepository->assignRole($userId, $roleId);
    }

    /**
     * Revoke a role from a user
     * 
     * @throws UserNotFoundException
     */
    public function revokeRole(string $userId, string $roleId): void
    {
        $this->userRepository->findById($userId); // Verify user exists
        $this->userRepository->revokeRole($userId, $roleId);
    }

    /**
     * Assign a permission directly to a user
     * 
     * @throws UserNotFoundException
     */
    public function assignPermission(string $userId, string $permissionId): void
    {
        $this->userRepository->findById($userId); // Verify user exists
        $this->userRepository->assignPermission($userId, $permissionId);
    }

    /**
     * Revoke a permission from a user
     * 
     * @throws UserNotFoundException
     */
    public function revokePermission(string $userId, string $permissionId): void
    {
        $this->userRepository->findById($userId); // Verify user exists
        $this->userRepository->revokePermission($userId, $permissionId);
    }

    /**
     * Find a user by ID
     * 
     * @throws UserNotFoundException
     */
    public function findUser(string $userId): UserInterface
    {
        return $this->userRepository->findById($userId);
    }

    /**
     * Find a user by email
     * 
     * @throws UserNotFoundException
     */
    public function findUserByEmail(string $email): UserInterface
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Search users by criteria
     * 
     * @param array<string, mixed> $criteria
     * @return UserInterface[]
     */
    public function searchUsers(array $criteria): array
    {
        return $this->userRepository->search($criteria);
    }
}
