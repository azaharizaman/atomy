<?php

declare(strict_types=1);

namespace Nexus\Tenant\Contracts;

/**
 * Tenant Repository Interface
 *
 * Defines all persistence operations for tenant entities.
 * This interface must be implemented by the application layer using Eloquent.
 *
 * @package Nexus\Tenant\Contracts
 */
interface TenantRepositoryInterface
{
    /**
     * Find a tenant by its unique ID.
     *
     * @param string $id
     * @return TenantInterface|null
     */
    public function findById(string $id): ?TenantInterface;

    /**
     * Find a tenant by its unique code.
     *
     * @param string $code
     * @return TenantInterface|null
     */
    public function findByCode(string $code): ?TenantInterface;

    /**
     * Find a tenant by its domain.
     *
     * @param string $domain
     * @return TenantInterface|null
     */
    public function findByDomain(string $domain): ?TenantInterface;

    /**
     * Find a tenant by its subdomain.
     *
     * @param string $subdomain
     * @return TenantInterface|null
     */
    public function findBySubdomain(string $subdomain): ?TenantInterface;

    /**
     * Get all tenants with optional filtering.
     *
     * @param array<string, mixed> $filters
     * @param int $page
     * @param int $perPage
     * @return array{data: array<TenantInterface>, total: int, page: int, perPage: int}
     */
    public function all(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * Create a new tenant.
     *
     * @param array<string, mixed> $data
     * @return TenantInterface
     */
    public function create(array $data): TenantInterface;

    /**
     * Update an existing tenant.
     *
     * @param string $id
     * @param array<string, mixed> $data
     * @return TenantInterface
     */
    public function update(string $id, array $data): TenantInterface;

    /**
     * Delete a tenant (soft delete).
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Permanently delete a tenant (hard delete).
     *
     * @param string $id
     * @return bool
     */
    public function forceDelete(string $id): bool;

    /**
     * Restore a soft-deleted tenant.
     *
     * @param string $id
     * @return TenantInterface
     */
    public function restore(string $id): TenantInterface;

    /**
     * Check if a tenant code is already in use.
     *
     * @param string $code
     * @param string|null $excludeId
     * @return bool
     */
    public function codeExists(string $code, ?string $excludeId = null): bool;

    /**
     * Check if a tenant domain is already in use.
     *
     * @param string $domain
     * @param string|null $excludeId
     * @return bool
     */
    public function domainExists(string $domain, ?string $excludeId = null): bool;

    /**
     * Get all active tenants.
     *
     * @return array<TenantInterface>
     */
    public function getActive(): array;

    /**
     * Get all suspended tenants.
     *
     * @return array<TenantInterface>
     */
    public function getSuspended(): array;

    /**
     * Get all trial tenants.
     *
     * @return array<TenantInterface>
     */
    public function getTrials(): array;

    /**
     * Get all expired trial tenants.
     *
     * @return array<TenantInterface>
     */
    public function getExpiredTrials(): array;

    /**
     * Get child tenants for a parent tenant.
     *
     * @param string $parentId
     * @return array<TenantInterface>
     */
    public function getChildren(string $parentId): array;

    /**
     * Get tenant statistics.
     *
     * @return array{total: int, active: int, suspended: int, trial: int, archived: int}
     */
    public function getStatistics(): array;

    /**
     * Search tenants by name, code, or email.
     *
     * @param string $query
     * @param int $limit
     * @return array<TenantInterface>
     */
    public function search(string $query, int $limit = 10): array;
}
