<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\TenantQueryInterface;
use Nexus\TenantOperations\Rules\TenantCodeCheckerInterface;
use Nexus\TenantOperations\Rules\TenantDomainCheckerInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingTenantQueryAdapter implements TenantQueryInterface, TenantCodeCheckerInterface, TenantDomainCheckerInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function findById(string $tenantId): ?array
    {
        $this->logger->info('Finding tenant by ID in canary adapter', ['tenantId' => $tenantId]);
        return null; // For canary, we assume none found initially
    }

    public function exists(string $tenantId): bool
    {
        $this->logger->info('Checking if tenant exists in canary adapter', ['tenantId' => $tenantId]);
        return false;
    }

    public function isCodeUnique(string $tenantCode): bool
    {
        $this->logger->info('Checking if tenant code is unique in canary adapter', ['tenantCode' => $tenantCode]);
        return true; // Always unique for canary testing
    }

    public function isDomainUnique(string $domain): bool
    {
        $this->logger->info('Checking if tenant domain is unique in canary adapter', ['domain' => $domain]);
        return true; // Always unique for canary testing
    }
}
