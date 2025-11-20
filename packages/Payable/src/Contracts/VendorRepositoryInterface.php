<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Repository interface for vendor persistence operations.
 */
interface VendorRepositoryInterface
{
    /**
     * Find vendor by ID.
     *
     * @param string $id Vendor ULID
     * @return VendorInterface|null
     */
    public function findById(string $id): ?VendorInterface;

    /**
     * Find vendor by code.
     *
     * @param string $tenantId Tenant ULID
     * @param string $code Unique vendor code
     * @return VendorInterface|null
     */
    public function findByCode(string $tenantId, string $code): ?VendorInterface;

    /**
     * Find vendor by tax ID.
     *
     * @param string $tenantId Tenant ULID
     * @param string $taxId Tax identification number
     * @return VendorInterface|null
     */
    public function findByTaxId(string $tenantId, string $taxId): ?VendorInterface;

    /**
     * Get all vendors for a tenant with optional filters.
     *
     * @param string $tenantId Tenant ULID
     * @param array $filters Optional filters (status, payment_terms)
     * @return array<VendorInterface>
     */
    public function getAll(string $tenantId, array $filters = []): array;

    /**
     * Save vendor (create or update).
     *
     * @param VendorInterface $vendor Vendor entity
     * @return VendorInterface
     */
    public function save(VendorInterface $vendor): VendorInterface;

    /**
     * Delete vendor.
     *
     * @param string $id Vendor ULID
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Check if vendor code exists.
     *
     * @param string $tenantId Tenant ULID
     * @param string $code Vendor code
     * @param string|null $excludeId Exclude specific vendor ID from check
     * @return bool
     */
    public function codeExists(string $tenantId, string $code, ?string $excludeId = null): bool;
}
