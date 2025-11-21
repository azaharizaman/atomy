<?php

declare(strict_types=1);

namespace App\Repositories\Warehouse;

use App\Models\BinLocation;
use Nexus\Warehouse\Contracts\BinLocationInterface;
use Nexus\Warehouse\Contracts\BinLocationRepositoryInterface;
use Nexus\Warehouse\Exceptions\WarehouseException;

final class DbBinLocationRepository implements BinLocationRepositoryInterface
{
    public function findById(string $id): ?BinLocationInterface
    {
        return BinLocation::find($id);
    }

    public function findByCode(string $warehouseId, string $code): ?BinLocationInterface
    {
        return BinLocation::where('warehouse_id', $warehouseId)
            ->where('code', $code)
            ->first();
    }

    public function findByWarehouse(string $warehouseId): array
    {
        return BinLocation::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->all();
    }

    public function save(BinLocationInterface $binLocation): void
    {
        if (!$binLocation instanceof BinLocation) {
            throw new WarehouseException('Invalid bin location instance');
        }

        $binLocation->save();
    }

    public function delete(string $id): void
    {
        BinLocation::where('id', $id)->delete();
    }
}
