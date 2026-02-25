<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\TenantCreatorAdapterInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class LoggingTenantCreatorAdapter implements TenantCreatorAdapterInterface
{
    public function create(string $code, string $name, string $domain): string
    {
        $tenantId = (string) Str::uuid();

        Log::info('Laravel Canary: Tenant record created', [
            'tenantId' => $tenantId,
            'code' => $code,
            'name' => $name,
            'domain' => $domain,
        ]);

        return $tenantId;
    }
}
