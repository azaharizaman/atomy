<?php

declare(strict_types=1);

namespace Nexus\Product\Contracts;

/**
 * Category Repository Interface
 *
 * Manages persistence of product categories.
 */
interface CategoryRepositoryInterface
{
    /**
     * Find category by ID
     *
     * @param string $id
     * @return CategoryInterface|null
     */
    public function findById(string $id): ?CategoryInterface;

    /**
     * Find category by code within tenant
     *
     * @param string $tenantId
     * @param string $code
     * @return CategoryInterface|null
     */
    public function findByCode(string $tenantId, string $code): ?CategoryInterface;

    /**
     * Get all categories for tenant
     *
     * @param string $tenantId
     * @param bool $activeOnly
     * @return array<CategoryInterface>
     */
    public function getAllForTenant(string $tenantId, bool $activeOnly = true): array;

    /**
     * Get child categories for a parent
     *
     * @param string $parentId
     * @param bool $activeOnly
     * @return array<CategoryInterface>
     */
    public function getChildren(string $parentId, bool $activeOnly = true): array;

    /**
     * Get root categories (no parent) for tenant
     *
     * @param string $tenantId
     * @param bool $activeOnly
     * @return array<CategoryInterface>
     */
    public function getRootCategories(string $tenantId, bool $activeOnly = true): array;

    /**
     * Get ancestor IDs for a category (for circular reference detection)
     *
     * @param string $categoryId
     * @return array<string>
     */
    public function getAncestorIds(string $categoryId): array;

    /**
     * Save category
     *
     * @param CategoryInterface $category
     * @return CategoryInterface
     */
    public function save(CategoryInterface $category): CategoryInterface;

    /**
     * Delete category
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;
}
