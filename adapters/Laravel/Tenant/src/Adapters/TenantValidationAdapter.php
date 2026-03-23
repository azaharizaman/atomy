<?php

declare(strict_types=1);

namespace Nexus\Laravel\Tenant\Adapters;

use Nexus\Tenant\Contracts\TenantValidationInterface;
use Psr\Log\LoggerInterface;

final readonly class TenantValidationAdapter implements TenantValidationInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $this->logger->debug('Validating tenant code', ['code' => $code, 'excludeId' => $excludeId]);

        $query = \App\Models\Tenant::where('code', $code);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function domainExists(string $domain, ?string $excludeId = null): bool
    {
        $this->logger->debug('Validating tenant domain', ['domain' => $domain, 'excludeId' => $excludeId]);

        $query = \App\Models\Tenant::where('domain', $domain);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function nameExists(string $name, ?string $excludeId = null): bool
    {
        $this->logger->debug('Validating tenant name', ['name' => $name, 'excludeId' => $excludeId]);

        $query = \App\Models\Tenant::where('name', $name);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}