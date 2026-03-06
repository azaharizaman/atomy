<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\AuditLoggerAdapterInterface;
use Illuminate\Support\Facades\Log;

final readonly class LoggingAuditLoggerAdapter implements AuditLoggerAdapterInterface
{
    public function log(string $event, string $tenantId, array $data): void
    {
        Log::info('Laravel Canary: Audit event logged', [
            'event' => $event,
            'tenantId' => $tenantId,
            'data' => $data,
        ]);
    }
}
