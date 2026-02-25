<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\TenantOperations\Contracts\TenantCreatorAdapterInterface;

final readonly class TenantCreatorAdapter implements TenantCreatorAdapterInterface
{
    public function __construct(
        private TenantPersistenceInterface $tenantPersistence
    ) {}

    public function create(string $code, string $name, string $domain): string
    {
        // Simple mapping for canary app. In real life, might need admin email too.
        $tenant = $this->tenantPersistence->create([
            'code' => $code,
            'name' => $name,
            'email' => "admin@$domain",
            'domain' => $domain,
            'status' => 'active',
        ]);

        return $tenant->getId();
    }
}
