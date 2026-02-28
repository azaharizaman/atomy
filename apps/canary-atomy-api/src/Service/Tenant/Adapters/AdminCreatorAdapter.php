<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Repository\UserRepository;
use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;

final readonly class AdminCreatorAdapter implements AdminCreatorAdapterInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function create(string $tenantId, string $email, string $password, bool $isAdmin = false): string
    {
        $user = $this->userRepository->create([
            'email' => $email,
            'name' => explode('@', $email)[0],
            'roles' => $isAdmin ? ['ROLE_TENANT_ADMIN'] : ['ROLE_USER'],
            'tenantId' => $tenantId,
        ]);

        return $user->getId();
    }
}
