<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use App\Repository\TenantRepository;
use Nexus\Tenant\Enums\TenantStatus;
use Nexus\TenantOperations\Services\TenantStateManagerInterface;

final readonly class TenantStateManager implements TenantStateManagerInterface
{
    public function __construct(private TenantRepository $repository) {}
    public function suspend(string $tenantId): void { $this->repository->update($tenantId, ['status' => TenantStatus::Suspended->value]); }
    public function activate(string $tenantId): void { $this->repository->update($tenantId, ['status' => TenantStatus::Active->value]); }
    public function archive(string $tenantId): void { $this->repository->update($tenantId, ['status' => TenantStatus::Archived->value]); }
}
