<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class LoggingAdminCreatorAdapter implements AdminCreatorAdapterInterface
{
    public function create(string $tenantId, string $email, string $password, bool $isAdmin = false): string
    {
        $adminId = (string) Str::uuid();

        Log::info('Laravel Canary: Admin user created', [
            'adminId' => $adminId,
            'tenantId' => $tenantId,
            'email' => $email,
            'isAdmin' => $isAdmin,
        ]);

        return $adminId;
    }
}
