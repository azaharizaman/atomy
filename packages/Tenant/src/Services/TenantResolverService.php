<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Tenant\ValueObjects\IdentificationStrategy;

/**
 * Tenant Resolver Service
 *
 * Identifies the current tenant from various request sources.
 *
 * @package Nexus\Tenant\Services
 */
class TenantResolverService
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository
    ) {
    }

    /**
     * Resolve tenant from full domain.
     *
     * @param string $domain
     * @return string|null Tenant ID
     */
    public function resolveFromDomain(string $domain): ?string
    {
        $tenant = $this->repository->findByDomain($domain);
        return $tenant?->getId();
    }

    /**
     * Resolve tenant from subdomain.
     *
     * @param string $fullDomain e.g., "acme.myapp.com"
     * @return string|null Tenant ID
     */
    public function resolveFromSubdomain(string $fullDomain): ?string
    {
        // Extract subdomain (first part before first dot)
        $parts = explode('.', $fullDomain);
        if (count($parts) < 2) {
            return null;
        }

        $subdomain = $parts[0];
        $tenant = $this->repository->findBySubdomain($subdomain);
        return $tenant?->getId();
    }

    /**
     * Resolve tenant from HTTP header.
     *
     * @param array<string, string> $headers
     * @param string $headerName
     * @return string|null Tenant ID
     */
    public function resolveFromHeader(array $headers, string $headerName = 'X-Tenant-ID'): ?string
    {
        $tenantId = $headers[$headerName] ?? $headers[strtolower($headerName)] ?? null;

        if (!$tenantId) {
            return null;
        }

        // Validate tenant exists
        $tenant = $this->repository->findById($tenantId);
        return $tenant?->getId();
    }

    /**
     * Resolve tenant from URL path.
     *
     * @param string $path e.g., "/tenant/acme/dashboard"
     * @param string $prefix e.g., "/tenant/"
     * @return string|null Tenant ID
     */
    public function resolveFromPath(string $path, string $prefix = '/tenant/'): ?string
    {
        if (!str_starts_with($path, $prefix)) {
            return null;
        }

        $remaining = substr($path, strlen($prefix));
        $parts = explode('/', $remaining);
        $tenantCode = $parts[0] ?? null;

        if (!$tenantCode) {
            return null;
        }

        $tenant = $this->repository->findByCode($tenantCode);
        return $tenant?->getId();
    }

    /**
     * Resolve tenant using the configured strategy.
     *
     * @param IdentificationStrategy $strategy
     * @param array<string, mixed> $context
     * @return string|null Tenant ID
     */
    public function resolve(IdentificationStrategy $strategy, array $context): ?string
    {
        return match (true) {
            $strategy->isDomain() => $this->resolveFromDomain($context['domain'] ?? ''),
            $strategy->isSubdomain() => $this->resolveFromSubdomain($context['domain'] ?? ''),
            $strategy->isHeader() => $this->resolveFromHeader(
                $context['headers'] ?? [],
                $context['header_name'] ?? 'X-Tenant-ID'
            ),
            $strategy->isPath() => $this->resolveFromPath(
                $context['path'] ?? '',
                $context['path_prefix'] ?? '/tenant/'
            ),
            default => null,
        };
    }
}
