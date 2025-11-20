<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Attribute;
use Nexus\Product\Contracts\AttributeRepositoryInterface;
use Nexus\Product\Contracts\AttributeSetInterface;

/**
 * Database Attribute Repository
 *
 * Eloquent implementation of AttributeRepositoryInterface.
 */
class DbAttributeRepository implements AttributeRepositoryInterface
{
    public function findById(string $id): ?AttributeSetInterface
    {
        return Attribute::find($id);
    }

    public function findByCode(string $tenantId, string $code): ?AttributeSetInterface
    {
        return Attribute::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    public function getAllForTenant(string $tenantId, bool $activeOnly = true): array
    {
        $query = Attribute::where('tenant_id', $tenantId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function getByCodes(string $tenantId, array $codes): array
    {
        return Attribute::where('tenant_id', $tenantId)
            ->whereIn('code', $codes)
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    public function save(AttributeSetInterface $attribute): AttributeSetInterface
    {
        if ($attribute instanceof Attribute) {
            $attribute->save();
            return $attribute;
        }

        // Create new attribute from interface
        $model = new Attribute();
        $model->tenant_id = $attribute->getTenantId();
        $model->code = $attribute->getCode();
        $model->name = $attribute->getName();
        $model->description = $attribute->getDescription();
        $model->values = $attribute->getValues();
        $model->sort_order = $attribute->getSortOrder();
        $model->is_active = $attribute->isActive();
        $model->save();

        return $model;
    }

    public function delete(string $id): bool
    {
        $attribute = Attribute::find($id);
        return $attribute?->delete() ?? false;
    }
}
