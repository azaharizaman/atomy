<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\CompanyCreatorAdapterInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class LoggingCompanyCreatorAdapter implements CompanyCreatorAdapterInterface
{
    public function createDefaultStructure(string $tenantId, string $companyName): string
    {
        $companyId = (string) Str::uuid();

        Log::info('Laravel Canary: Company structure created', [
            'companyId' => $companyId,
            'tenantId' => $tenantId,
            'companyName' => $companyName,
        ]);

        return $companyId;
    }
}
