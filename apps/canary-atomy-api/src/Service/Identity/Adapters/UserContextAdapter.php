<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use App\Repository\UserRepository;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\UserContext;
use Nexus\Identity\ValueObjects\UserStatus;

final readonly class UserContextAdapter implements UserContextProviderInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function getContext(string $userId): UserContext
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            return UserContext::anonymous();
        }

        // Split name into first and last if needed.
        $nameParts = explode(' ', $user->getName() ?? '', 2);
        $firstName = $nameParts[0] ?? null;
        $lastName = $nameParts[1] ?? null;

        return new UserContext(
            userId: $user->getId(),
            email: $user->getEmail(),
            firstName: $firstName,
            lastName: $lastName,
            tenantId: $user->getTenantId(),
            status: $user->getStatus()->value,
            permissions: [], // Permissions will be fetched through UserPermissionService
            roles: $user->getRoles()
        );
    }

    public function userExists(string $userId): bool
    {
        return $this->userRepository->find($userId) !== null;
    }

    public function isUserActive(string $userId): bool
    {
        $user = $this->userRepository->find($userId);

        return $user && $user->getStatus() === UserStatus::ACTIVE;
    }
}
