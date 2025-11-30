<?php

declare(strict_types=1);

namespace Nexus\Product\Contracts;

/**
 * Attribute Repository Interface
 *
 * Manages persistence of attribute sets and their values.
 */
interface AttributeRepositoryInterface
{
    /**
     * Find attribute by ID
     *
     * @param string $id
     * @return AttributeSetInterface|null
     */
    public function findById(string $id): ?AttributeSetInterface;

    /**
     * Find attribute by code within tenant
     *
     * @param string $tenantId
     * @param string $code
     * @return AttributeSetInterface|null
     */
    public function findByCode(string $tenantId, string $code): ?AttributeSetInterface;

    /**
     * Get all attributes for tenant
     *
     * @param string $tenantId
     * @param bool $activeOnly
     * @return array<AttributeSetInterface>
     */
    public function getAllForTenant(string $tenantId, bool $activeOnly = true): array;

    /**
     * Get attributes by codes
     *
     * @param string $tenantId
     * @param array<string> $codes
     * @return array<AttributeSetInterface>
     */
    public function getByCodes(string $tenantId, array $codes): array;

    /**
     * Save attribute
     *
     * @param AttributeSetInterface $attribute
     * @return AttributeSetInterface
     */
    public function save(AttributeSetInterface $attribute): AttributeSetInterface;

    /**
     * Delete attribute
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;
}
