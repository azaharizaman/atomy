<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Entity\User;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\ValueObjects\RoleEnum;
use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;

final readonly class AdminCreatorAdapter implements AdminCreatorAdapterInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher
    ) {}

    public function create(string $tenantId, string $email, string $password, bool $isAdmin = false): string
    {
        $hashedPassword = $this->passwordHasher->hash($password);

        $user = $this->userRepository->create([
            'email' => $email,
            'name' => explode('@', $email)[0],
            'password' => $hashedPassword,
            'roles' => $isAdmin ? [RoleEnum::TENANT_ADMIN->value] : [RoleEnum::USER->value],
            'tenantId' => $tenantId,
        ]);

        return $user->getId();
    }
}
