<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use App\Repository\UserRepository;
use App\Repository\TenantRepository;
use Nexus\IdentityOperations\Services\AuthenticatorInterface;
use Nexus\Identity\ValueObjects\UserStatus;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AuthenticatorAdapter implements AuthenticatorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private TenantRepository $tenantRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function authenticate(string $email, string $password, ?string $tenantId = null): array
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if ($tenantId !== null) {
            // Resolve tenantId if it's a code
            $resolvedTenantId = $tenantId;
            $tenant = $this->tenantRepository->findByCode($tenantId);
            if ($tenant) {
                $resolvedTenantId = $tenant->getId();
            }

            if ($user->getTenantId() !== $resolvedTenantId) {
                throw new \RuntimeException('User not found in this tenant');
            }
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new \RuntimeException('Invalid password');
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => explode(' ', $user->getName() ?? '', 2)[0] ?? null,
            'last_name' => explode(' ', $user->getName() ?? '', 2)[1] ?? null,
            'status' => $user->getStatus(),
            'permissions' => [], // Permissions will be fetched through UserPermissionService
            'roles' => $user->getRoles()
        ];
    }

    public function getUserById(string $userId): array
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => explode(' ', $user->getName() ?? '', 2)[0] ?? null,
            'last_name' => explode(' ', $user->getName() ?? '', 2)[1] ?? null,
            'status' => $user->getStatus(),
            'permissions' => [], // Permissions will be fetched through UserPermissionService
            'roles' => $user->getRoles()
        ];
    }
}
