<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\TenantCreatorAdapterInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingTenantCreatorAdapter implements TenantCreatorAdapterInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function create(string $code, string $name, string $domain): string
    {
        $tenantId = (string) \Symfony\Component\Uid\Uuid::v4();

        $this->logger->info('Tenant record created in canary adapter', [
            'tenantId' => $tenantId,
            'code' => $code,
            'name' => $name,
            'domain' => $domain,
        ]);

        return $tenantId;
    }
}
