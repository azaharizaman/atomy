<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use Nexus\Product\Contracts\CategoryInterface;
use Nexus\Product\Contracts\CategoryRepositoryInterface;

/**
 * Database Category Repository
 *
 * Eloquent implementation of CategoryRepositoryInterface.
 */
class DbCategoryRepository implements CategoryRepositoryInterface
{
    public function findById(string $id): ?CategoryInterface
    {
        return Category::find($id);
    }

    public function findByCode(string $tenantId, string $code): ?CategoryInterface
    {
        return Category::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    public function getAllForTenant(string $tenantId, bool $activeOnly = true): array
    {
        $query = Category::where('tenant_id', $tenantId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function getChildren(string $parentId, bool $activeOnly = true): array
    {
        $query = Category::where('parent_id', $parentId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function getRootCategories(string $tenantId, bool $activeOnly = true): array
    {
        $query = Category::where('tenant_id', $tenantId)
            ->whereNull('parent_id');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function getAncestorIds(string $categoryId): array
    {
        $ancestors = [];
        $currentId = $categoryId;

        while ($currentId !== null) {
            $category = Category::find($currentId);
            
            if ($category === null) {
                break;
            }

            if ($category->parent_id !== null) {
                $ancestors[] = $category->parent_id;
            }

            $currentId = $category->parent_id;
        }

        return $ancestors;
    }

    public function save(CategoryInterface $category): CategoryInterface
    {
        if ($category instanceof Category) {
            $category->save();
            return $category;
        }

        // Create new category from interface
        $model = new Category();
        $model->tenant_id = $category->getTenantId();
        $model->code = $category->getCode();
        $model->name = $category->getName();
        $model->description = $category->getDescription();
        $model->parent_id = $category->getParentId();
        $model->sort_order = $category->getSortOrder();
        $model->is_active = $category->isActive();
        $model->save();

        return $model;
    }

    public function delete(string $id): bool
    {
        $category = Category::find($id);
        return $category?->delete() ?? false;
    }
}
