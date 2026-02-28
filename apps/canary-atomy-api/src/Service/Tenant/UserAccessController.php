<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use App\Repository\UserRepository;
use Nexus\Identity\ValueObjects\UserStatus;
use Nexus\TenantOperations\Services\UserAccessControllerInterface;

final readonly class UserAccessController implements UserAccessControllerInterface
{
    public function __construct(private UserRepository $repository) {}
    public function disable(string $tenantId): void {
        foreach ($this->repository->findBy(['tenantId' => $tenantId]) as $user) {
            $this->repository->update($user->getId(), ['status' => UserStatus::SUSPENDED->value]);
        }
    }
    public function enable(string $tenantId): void {
        foreach ($this->repository->findBy(['tenantId' => $tenantId]) as $user) {
            $this->repository->update($user->getId(), ['status' => UserStatus::ACTIVE->value]);
        }
    }
}
