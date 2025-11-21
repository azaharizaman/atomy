<?php

declare(strict_types=1);

namespace App\Repositories\Inventory;

use App\Models\StockReservation;
use Nexus\Inventory\Contracts\ReservationRepositoryInterface;

final class DbReservationRepository implements ReservationRepositoryInterface
{
    public function create(array $data): string
    {
        $reservation = StockReservation::create([
            'tenant_id' => $data['tenant_id'],
            'product_id' => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'],
            'quantity' => $data['quantity'],
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'reserved_at' => now(),
            'expires_at' => $data['expires_at'],
        ]);

        return $reservation->id;
    }

    public function findById(string $id): ?array
    {
        $reservation = StockReservation::find($id);
        return $reservation ? $this->toArray($reservation) : null;
    }

    public function getTotalReserved(string $productId, string $warehouseId): float
    {
        return (float) StockReservation::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->sum('quantity');
    }

    public function markFulfilled(string $id): void
    {
        StockReservation::where('id', $id)->update([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
        ]);
    }

    public function markReleased(string $id): void
    {
        StockReservation::where('id', $id)->update([
            'status' => 'released',
        ]);
    }

    public function markExpired(string $id): void
    {
        StockReservation::where('id', $id)->update([
            'status' => 'expired',
        ]);
    }

    public function findExpired(): array
    {
        return StockReservation::where('status', 'active')
            ->where('expires_at', '<', now())
            ->get()
            ->map(fn($res) => $this->toArray($res))
            ->all();
    }

    private function toArray(StockReservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'product_id' => $reservation->product_id,
            'warehouse_id' => $reservation->warehouse_id,
            'quantity' => (float) $reservation->quantity,
            'reference_type' => $reservation->reference_type,
            'reference_id' => $reservation->reference_id,
            'reserved_at' => $reservation->reserved_at->toIso8601String(),
            'expires_at' => $reservation->expires_at->toIso8601String(),
            'status' => $reservation->status,
            'fulfilled_at' => $reservation->fulfilled_at?->toIso8601String(),
        ];
    }
}
