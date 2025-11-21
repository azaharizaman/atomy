<?php

declare(strict_types=1);

namespace App\Repositories\Inventory;

use App\Models\StockTransfer;
use Nexus\Inventory\Contracts\TransferRepositoryInterface;

final class DbTransferRepository implements TransferRepositoryInterface
{
    public function create(array $data): string
    {
        $transfer = StockTransfer::create([
            'tenant_id' => $data['tenant_id'],
            'transfer_number' => $data['transfer_number'],
            'product_id' => $data['product_id'],
            'from_warehouse_id' => $data['from_warehouse_id'],
            'to_warehouse_id' => $data['to_warehouse_id'],
            'quantity' => $data['quantity'],
            'reference_id' => $data['reference_id'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return $transfer->id;
    }

    public function findById(string $id): ?array
    {
        $transfer = StockTransfer::find($id);
        return $transfer ? $this->toArray($transfer) : null;
    }

    public function updateStatus(string $id, string $status): void
    {
        $update = ['status' => $status];

        if ($status === 'in_transit') {
            $update['shipped_at'] = now();
        } elseif ($status === 'completed') {
            $update['received_at'] = now();
        }

        StockTransfer::where('id', $id)->update($update);
    }

    public function updateReceivedQuantity(string $id, float $quantity): void
    {
        StockTransfer::where('id', $id)->update([
            'received_quantity' => $quantity,
        ]);
    }

    public function getNextTransferNumber(string $tenantId): string
    {
        $lastTransfer = StockTransfer::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastTransfer) {
            return 'ST-' . date('Y') . '-00001';
        }

        // Extract number from ST-YYYY-NNNNN
        preg_match('/ST-\d{4}-(\d+)/', $lastTransfer->transfer_number, $matches);
        $nextNum = isset($matches[1]) ? (int) $matches[1] + 1 : 1;

        return 'ST-' . date('Y') . '-' . str_pad((string) $nextNum, 5, '0', STR_PAD_LEFT);
    }

    private function toArray(StockTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'transfer_number' => $transfer->transfer_number,
            'product_id' => $transfer->product_id,
            'from_warehouse_id' => $transfer->from_warehouse_id,
            'to_warehouse_id' => $transfer->to_warehouse_id,
            'quantity' => (float) $transfer->quantity,
            'received_quantity' => $transfer->received_quantity ? (float) $transfer->received_quantity : null,
            'status' => $transfer->status,
            'shipped_at' => $transfer->shipped_at?->toIso8601String(),
            'received_at' => $transfer->received_at?->toIso8601String(),
        ];
    }
}
