<?php

declare(strict_types=1);

namespace App\Repositories\Inventory;

use App\Models\SerialNumber;
use Nexus\Inventory\Contracts\SerialRepositoryInterface;

final class DbSerialRepository implements SerialRepositoryInterface
{
    public function findBySerial(string $tenantId, string $serial): ?array
    {
        $serialNumber = SerialNumber::where('tenant_id', $tenantId)
            ->where('serial_number', $serial)
            ->first();

        return $serialNumber ? $this->toArray($serialNumber) : null;
    }

    public function findByProduct(string $productId, ?string $warehouseId = null): array
    {
        $query = SerialNumber::where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->map(fn($sn) => $this->toArray($sn))->all();
    }

    public function create(array $data): string
    {
        $serial = SerialNumber::create([
            'tenant_id' => $data['tenant_id'],
            'serial_number' => $data['serial_number'],
            'product_id' => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'lot_id' => $data['lot_id'] ?? null,
        ]);

        return $serial->id;
    }

    public function markIssued(string $serialId, string $referenceId): void
    {
        SerialNumber::where('id', $serialId)->update([
            'status' => 'issued',
            'issued_reference_id' => $referenceId,
            'issued_at' => now(),
        ]);
    }

    public function markAvailable(string $serialId): void
    {
        SerialNumber::where('id', $serialId)->update([
            'status' => 'available',
            'issued_reference_id' => null,
            'issued_at' => null,
        ]);
    }

    private function toArray(SerialNumber $serial): array
    {
        return [
            'id' => $serial->id,
            'serial_number' => $serial->serial_number,
            'product_id' => $serial->product_id,
            'warehouse_id' => $serial->warehouse_id,
            'lot_id' => $serial->lot_id,
            'status' => $serial->status,
            'issued_reference_id' => $serial->issued_reference_id,
            'issued_at' => $serial->issued_at?->toIso8601String(),
        ];
    }
}
