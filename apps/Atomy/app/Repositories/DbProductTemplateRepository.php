<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductTemplate;
use Nexus\Product\Contracts\ProductTemplateInterface;
use Nexus\Product\Contracts\ProductTemplateRepositoryInterface;

/**
 * Database Product Template Repository
 *
 * Eloquent implementation of ProductTemplateRepositoryInterface.
 */
class DbProductTemplateRepository implements ProductTemplateRepositoryInterface
{
    public function findById(string $id): ?ProductTemplateInterface
    {
        return ProductTemplate::find($id);
    }

    public function findByCode(string $tenantId, string $code): ?ProductTemplateInterface
    {
        return ProductTemplate::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    public function getAllForTenant(string $tenantId, bool $activeOnly = true): array
    {
        $query = ProductTemplate::where('tenant_id', $tenantId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')
            ->get()
            ->all();
    }

    public function getByCategory(string $tenantId, string $categoryCode, bool $activeOnly = true): array
    {
        $query = ProductTemplate::where('tenant_id', $tenantId)
            ->where('category_code', $categoryCode);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')
            ->get()
            ->all();
    }

    public function save(ProductTemplateInterface $template): ProductTemplateInterface
    {
        if ($template instanceof ProductTemplate) {
            $template->save();
            return $template;
        }

        // Create new template from interface
        $model = new ProductTemplate();
        $model->tenant_id = $template->getTenantId();
        $model->code = $template->getCode();
        $model->name = $template->getName();
        $model->description = $template->getDescription();
        $model->category_code = $template->getCategoryCode();
        $model->is_active = $template->isActive();
        $model->metadata = $template->getMetadata();
        $model->save();

        return $model;
    }

    public function delete(string $id): bool
    {
        $template = ProductTemplate::find($id);
        return $template?->delete() ?? false;
    }
}
