<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\TenantQueryInterface;
use Nexus\TenantOperations\Rules\TenantCodeCheckerInterface;
use Nexus\TenantOperations\Rules\TenantDomainCheckerInterface;
use Illuminate\Support\Facades\Log;

final readonly class LoggingTenantQueryAdapter implements TenantQueryInterface, TenantCodeCheckerInterface, TenantDomainCheckerInterface
{
    public function findById(string $tenantId): ?array
    {
        Log::info('Laravel Canary: Finding tenant by ID', ['tenantId' => $tenantId]);
        return null;
    }

    public function exists(string $tenantId): bool
    {
        Log::info('Laravel Canary: Checking if tenant exists', ['tenantId' => $tenantId]);
        return false;
    }

    public function isCodeUnique(string $tenantCode): bool
    {
        Log::info('Laravel Canary: Checking if code is unique', ['tenantCode' => $tenantCode]);
        return true;
    }

    public function isDomainUnique(string $domain): bool
    {
        Log::info('Laravel Canary: Checking if domain is unique', ['domain' => $domain]);
        return true;
    }
}
