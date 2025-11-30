<?php

declare(strict_types=1);

namespace Nexus\Tenant\Contracts;

/**
 * Tenant Persistence Interface (Write Model - CQRS)
 *
 * Handles CREATE, UPDATE, DELETE operations for tenant entities.
 * This interface is responsible for maintaining the source of truth (Write Model).
 *
 * Following Interface Segregation Principle (ISP): Focused only on persistence operations.
 *
 * @package Nexus\Tenant\Contracts
 */
interface TenantPersistenceInterface
{
    /**
     * Create a new tenant.
     *
     * @param array<string, mixed> $data {
     *     @type string $code Unique tenant code
     *     @type string $name Tenant display name
     *     @type string $email Primary email address
     *     @type string|null $domain Primary domain
     *     @type string|null $subdomain Subdomain
     *     @type string $status Tenant status (pending, active, suspended, archived, trial)
     *     @type array<string, mixed>|null $metadata Additional metadata
     * }
     * @return TenantInterface
     */
    public function create(array $data): TenantInterface;

    /**
     * Update an existing tenant.
     *
     * @param string $id Tenant ULID
     * @param array<string, mixed> $data Partial data to update
     * @return TenantInterface Updated tenant entity
     * @throws \Nexus\Tenant\Exceptions\TenantNotFoundException
     */
    public function update(string $id, array $data): TenantInterface;

    /**
     * Delete a tenant (soft delete).
     *
     * @param string $id Tenant ULID
     * @return bool True on success
     * @throws \Nexus\Tenant\Exceptions\TenantNotFoundException
     */
    public function delete(string $id): bool;

    /**
     * Permanently delete a tenant (hard delete).
     *
     * @param string $id Tenant ULID
     * @return bool True on success
     * @throws \Nexus\Tenant\Exceptions\TenantNotFoundException
     */
    public function forceDelete(string $id): bool;

    /**
     * Restore a soft-deleted tenant.
     *
     * @param string $id Tenant ULID
     * @return TenantInterface Restored tenant entity
     * @throws \Nexus\Tenant\Exceptions\TenantNotFoundException
     */
    public function restore(string $id): TenantInterface;
}
