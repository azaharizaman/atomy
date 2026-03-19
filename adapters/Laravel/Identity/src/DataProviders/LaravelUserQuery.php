<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\DataProviders;

use Nexus\Identity\Contracts\UserQueryInterface as IdentityUserQueryInterface;
use Nexus\IdentityOperations\DataProviders\UserQueryInterface;

final readonly class LaravelUserQuery implements UserQueryInterface
{
    public function __construct(private IdentityUserQueryInterface $users)
    {
    }

    public function findById(string $userId): ?array
    {
        try {
            $user = $this->users->findById($userId);
        } catch (\Throwable) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getName() ?? '',
            'last_name' => '',
            'tenant_id' => $user->getTenantId(),
            'status' => $user->getStatus(),
        ];
    }

    public function exists(string $userId): bool
    {
        try {
            $this->users->findById($userId);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function isActive(string $userId): bool
    {
        $row = $this->findById($userId);
        if ($row === null) {
            return false;
        }
        return $row['status'] === 'active';
    }
}

