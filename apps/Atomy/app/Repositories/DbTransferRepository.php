<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StaffTransfer;
use Nexus\Backoffice\Contracts\TransferInterface;
use Nexus\Backoffice\Contracts\TransferRepositoryInterface;

class DbTransferRepository implements TransferRepositoryInterface
{
    public function findById(string $id): ?TransferInterface { return StaffTransfer::find($id); }
    public function getByStaff(string $staffId): array { return StaffTransfer::where('staff_id', $staffId)->get()->all(); }
    public function getPendingTransfers(): array { return StaffTransfer::where('status', 'pending')->get()->all(); }
    public function getPendingByStaff(string $staffId): array { return StaffTransfer::where('staff_id', $staffId)->where('status', 'pending')->get()->all(); }
    public function save(array $data): TransferInterface { return StaffTransfer::create($data); }
    public function update(string $id, array $data): TransferInterface { $transfer = StaffTransfer::findOrFail($id); $transfer->update($data); return $transfer->fresh(); }
    public function delete(string $id): bool { return StaffTransfer::destroy($id) > 0; }
    public function hasPendingTransfer(string $staffId): bool { return StaffTransfer::where('staff_id', $staffId)->where('status', 'pending')->exists(); }
    public function markAsApproved(string $id, string $approvedBy, string $comment): void { $transfer = StaffTransfer::findOrFail($id); $transfer->update(['status' => 'approved', 'approved_by' => $approvedBy, 'approved_at' => now()]); }
    public function markAsRejected(string $id, string $rejectedBy, string $reason): void { $transfer = StaffTransfer::findOrFail($id); $transfer->update(['status' => 'rejected', 'rejected_by' => $rejectedBy, 'rejected_at' => now(), 'rejection_reason' => $reason]); }
    public function markAsCompleted(string $id): void { $transfer = StaffTransfer::findOrFail($id); $transfer->update(['status' => 'completed', 'completed_at' => now()]); }
}
