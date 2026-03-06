<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\CompanyCreatorAdapterInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingCompanyCreatorAdapter implements CompanyCreatorAdapterInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function createDefaultStructure(string $tenantId, string $companyName): string
    {
        $companyId = (string) \Symfony\Component\Uid\Uuid::v4();

        $this->logger->info('Default company structure created in canary adapter', [
            'companyId' => $companyId,
            'tenantId' => $tenantId,
            'companyName' => $companyName,
        ]);

        return $companyId;
    }
}
