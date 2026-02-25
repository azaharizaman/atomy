<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use Nexus\TenantOperations\Contracts\CompanyCreatorAdapterInterface;

final readonly class CompanyCreatorAdapter implements CompanyCreatorAdapterInterface
{
    public function createDefaultStructure(string $tenantId, string $companyName): string
    {
        // Simulate creating company structure in Backoffice package.
        return 'COMP-' . bin2hex(random_bytes(8));
    }
}
