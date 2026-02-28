<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Repository\TenantRepository;
use Nexus\TenantOperations\Contracts\TenantCreatorAdapterInterface;

final readonly class TenantCreatorAdapter implements TenantCreatorAdapterInterface
{
    public function __construct(
        private TenantRepository $tenantRepository
    ) {}

    public function create(string $code, string $name, string $domain): string
    {
        $tenant = $this->tenantRepository->create([
            'code' => $code,
            'name' => $name,
            'email' => 'admin@' . $domain,
            'domain' => $domain,
            'status' => 'active',
        ]);

        return $tenant->getId();
    }
}
