<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Backoffice\Transfer;
use Nexus\Backoffice\Contracts\TransferInterface;
use Nexus\Backoffice\Contracts\TransferRepositoryInterface;
use Nexus\Backoffice\ValueObjects\TransferStatus;

class EloquentTransferRepository implements TransferRepositoryInterface
{
    public function findById(string $id): ?TransferInterface
    {
        return Transfer::find($id);
    }

    public function getByStaff(string $staffId): array
    {
        return Transfer::where('staff_id', $staffId)->get()->all();
    }

    public function getPendingTransfers(): array
    {
        return Transfer::where('status', TransferStatus::PENDING->value)->get()->all();
    }

    public function getPendingByStaff(string $staffId): array
    {
        return Transfer::where('staff_id', $staffId)
            ->where('status', TransferStatus::PENDING->value)
            ->get()
            ->all();
    }

    public function save(array $data): TransferInterface
    {
        if (isset($data['transfer_type'])) {
            $data['type'] = $data['transfer_type'];
            unset($data['transfer_type']);
        }
        return Transfer::create($data);
    }

    public function update(string $id, array $data): TransferInterface
    {
        $transfer = Transfer::findOrFail($id);
        $transfer->update($data);
        return $transfer;
    }

    public function delete(string $id): bool
    {
        return (bool) Transfer::destroy($id);
    }

    public function hasPendingTransfer(string $staffId): bool
    {
        return Transfer::where('staff_id', $staffId)
            ->where('status', TransferStatus::PENDING->value)
            ->exists();
    }

    public function markAsApproved(string $id, string $approvedBy, string $comment): void
    {
        $transfer = Transfer::findOrFail($id);
        $transfer->update([
            'status' => TransferStatus::APPROVED->value,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            // Assuming comment is stored in metadata or reason if applicable, 
            // but interface has getReason() which maps to reason column.
            // However, markAsApproved usually implies approval comment.
            // Let's store it in metadata for now as reason is usually for request/rejection.
            'metadata' => array_merge($transfer->metadata ?? [], ['approval_comment' => $comment]),
        ]);
    }

    public function markAsRejected(string $id, string $rejectedBy, string $reason): void
    {
        $transfer = Transfer::findOrFail($id);
        $transfer->update([
            'status' => TransferStatus::REJECTED->value,
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function markAsCompleted(string $id): void
    {
        $transfer = Transfer::findOrFail($id);
        $transfer->update([
            'status' => TransferStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);
    }
}
