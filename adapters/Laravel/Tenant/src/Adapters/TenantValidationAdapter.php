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

    private function hashIdentifier(string $value): string
    {
        return substr(hash('sha256', $value), 0, 8);
    }

    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $this->logger->debug('Validating tenant code', ['codeHash' => $this->hashIdentifier($code), 'excludeIdProvided' => $excludeId !== null]);

        $query = \App\Models\Tenant::where('code', $code);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function domainExists(string $domain, ?string $excludeId = null): bool
    {
        $this->logger->debug('Validating tenant domain', ['domainHash' => $this->hashIdentifier($domain), 'excludeIdProvided' => $excludeId !== null]);

        $query = \App\Models\Tenant::where('domain', $domain);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function nameExists(string $name, ?string $excludeId = null): bool
    {
        $this->logger->debug('Validating tenant name', ['nameHash' => $this->hashIdentifier($name), 'excludeIdProvided' => $excludeId !== null]);

        $query = \App\Models\Tenant::where('name', $name);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}