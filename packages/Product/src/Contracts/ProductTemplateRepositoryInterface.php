<?php

declare(strict_types=1);

namespace Nexus\Product\Contracts;

/**
 * Product Template Repository Interface
 *
 * Manages persistence of product templates.
 */
interface ProductTemplateRepositoryInterface
{
    /**
     * Find template by ID
     *
     * @param string $id
     * @return ProductTemplateInterface|null
     */
    public function findById(string $id): ?ProductTemplateInterface;

    /**
     * Find template by code within tenant
     *
     * @param string $tenantId
     * @param string $code
     * @return ProductTemplateInterface|null
     */
    public function findByCode(string $tenantId, string $code): ?ProductTemplateInterface;

    /**
     * Get all templates for tenant
     *
     * @param string $tenantId
     * @param bool $activeOnly
     * @return array<ProductTemplateInterface>
     */
    public function getAllForTenant(string $tenantId, bool $activeOnly = true): array;

    /**
     * Get templates by category
     *
     * @param string $tenantId
     * @param string $categoryCode
     * @param bool $activeOnly
     * @return array<ProductTemplateInterface>
     */
    public function getByCategory(string $tenantId, string $categoryCode, bool $activeOnly = true): array;

    /**
     * Save template
     *
     * @param ProductTemplateInterface $template
     * @return ProductTemplateInterface
     */
    public function save(ProductTemplateInterface $template): ProductTemplateInterface;

    /**
     * Delete template
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;
}
