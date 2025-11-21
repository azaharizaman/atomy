<?php

declare(strict_types=1);

namespace App\Repositories\Warehouse;

use App\Models\Warehouse;
use Nexus\Warehouse\Contracts\WarehouseInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Nexus\Warehouse\Exceptions\WarehouseException;

final class DbWarehouseRepository implements WarehouseRepositoryInterface
{
    public function findById(string $id): ?WarehouseInterface
    {
        return Warehouse::find($id);
    }

    public function findByCode(string $tenantId, string $code): ?WarehouseInterface
    {
        return Warehouse::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    public function findByTenant(string $tenantId): array
    {
        return Warehouse::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->all();
    }

    public function save(WarehouseInterface $warehouse): void
    {
        if (!$warehouse instanceof Warehouse) {
            throw new WarehouseException('Invalid warehouse instance');
        }

        $warehouse->save();
    }

    public function delete(string $id): void
    {
        Warehouse::where('id', $id)->delete();
    }
}
