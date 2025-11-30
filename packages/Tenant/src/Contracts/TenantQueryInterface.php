<?php

declare(strict_types=1);

namespace Nexus\Tenant\Contracts;

/**
 * Tenant Query Interface (Read Model - CQRS)
 *
 * Provides simple query operations for retrieving tenant entities.
 * Returns raw collections without pagination or complex filtering.
 *
 * Following Interface Segregation Principle (ISP): Focused only on basic queries.
 * Complex reporting and pagination should be handled in Application Layer Read Models.
 *
 * @package Nexus\Tenant\Contracts
 */
interface TenantQueryInterface
{
    /**
     * Find a tenant by its unique ID.
     *
     * @param string $id Tenant ULID
     * @return TenantInterface|null Tenant entity or null if not found
     */
    public function findById(string $id): ?TenantInterface;

    /**
     * Find a tenant by its unique code.
     *
     * @param string $code Tenant code (e.g., "ACME", "CORP01")
     * @return TenantInterface|null Tenant entity or null if not found
     */
    public function findByCode(string $code): ?TenantInterface;

    /**
     * Find a tenant by its domain.
     *
     * @param string $domain Primary domain (e.g., "acme.example.com")
     * @return TenantInterface|null Tenant entity or null if not found
     */
    public function findByDomain(string $domain): ?TenantInterface;

    /**
     * Find a tenant by its subdomain.
     *
     * @param string $subdomain Subdomain (e.g., "acme")
     * @return TenantInterface|null Tenant entity or null if not found
     */
    public function findBySubdomain(string $subdomain): ?TenantInterface;

    /**
     * Get all tenants (raw collection without pagination).
     *
     * Note: For large datasets, consider implementing pagination in the Application Layer.
     *
     * @return array<TenantInterface> Raw collection of all tenants
     */
    public function all(): array;

    /**
     * Get child tenants for a parent tenant.
     *
     * @param string $parentId Parent tenant ULID
     * @return array<TenantInterface> Collection of child tenants
     */
    public function getChildren(string $parentId): array;
}
