<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Services;

use Nexus\Backoffice\Contracts\TransferInterface;
use Nexus\Backoffice\Contracts\TransferManagerInterface;
use Nexus\Backoffice\Contracts\TransferRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\ValueObjects\TransferStatus;
use Nexus\Backoffice\ValueObjects\TransferType;
use Nexus\Backoffice\ValueObjects\StaffStatus;
use Nexus\Backoffice\Exceptions\StaffNotFoundException;
use Nexus\Backoffice\Exceptions\TransferNotFoundException;
use Nexus\Backoffice\Exceptions\InvalidTransferException;
use Nexus\Backoffice\Exceptions\InvalidOperationException;

/**
 * Service for managing staff transfer workflows.
 */
class TransferManager implements TransferManagerInterface
{
    public function __construct(
        private readonly TransferRepositoryInterface $transferRepository,
        private readonly StaffRepositoryInterface $staffRepository,
    ) {}

    public function createTransferRequest(array $data): TransferInterface
    {
        // Validate required fields
        if (empty($data['staff_id'])) {
            throw new \InvalidArgumentException('Staff ID is required');
        }
        if (empty($data['transfer_type'])) {
            throw new \InvalidArgumentException('Transfer type is required');
        }
        if (empty($data['effective_date'])) {
            throw new \InvalidArgumentException('Effective date is required');
        }

        // Validate staff exists
        $staff = $this->staffRepository->findById($data['staff_id']);
        if (!$staff) {
            throw new StaffNotFoundException($data['staff_id']);
        }

        // Validate staff is active
        $status = StaffStatus::from($staff->getStatus());
        if ($status !== StaffStatus::ACTIVE) {
            throw InvalidOperationException::inactiveEntity('Staff', $data['staff_id']);
        }

        // Check for pending transfers
        $pendingTransfers = $this->transferRepository->getPendingByStaff($data['staff_id']);
        if (count($pendingTransfers) > 0) {
            throw InvalidTransferException::pendingTransferExists($data['staff_id']);
        }

        // Validate effective date is not too far in the past (max 30 days)
        $effectiveDate = $data['effective_date'] instanceof \DateTimeInterface ? 
            $data['effective_date'] : new \DateTime($data['effective_date']);
        
        $thirtyDaysAgo = new \DateTime('-30 days');
        if ($effectiveDate < $thirtyDaysAgo) {
            throw InvalidTransferException::retroactiveDate($effectiveDate);
        }

        // Validate transfer type
        try {
            TransferType::from($data['transfer_type']);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException('Invalid transfer type: ' . $data['transfer_type']);
        }

        // Set default status
        if (empty($data['status'])) {
            $data['status'] = TransferStatus::PENDING->value;
        }

        return $this->transferRepository->save($data);
    }

    public function approveTransfer(string $transferId, string $approvedBy, string $comment): TransferInterface
    {
        $transfer = $this->transferRepository->findById($transferId);
        if (!$transfer) {
            throw new TransferNotFoundException($transferId);
        }

        // Validate current status is pending
        $status = TransferStatus::from($transfer->getStatus());
        if ($status !== TransferStatus::PENDING) {
            throw InvalidTransferException::invalidStatus($transferId, $status->value, TransferStatus::PENDING->value);
        }

        // Mark as approved
        $this->transferRepository->markAsApproved($transferId, $approvedBy, $comment);
        
        return $this->transferRepository->findById($transferId);
    }

    public function rejectTransfer(string $transferId, string $rejectedBy, string $reason): TransferInterface
    {
        $transfer = $this->transferRepository->findById($transferId);
        if (!$transfer) {
            throw new TransferNotFoundException($transferId);
        }

        // Validate current status is pending
        $status = TransferStatus::from($transfer->getStatus());
        if ($status !== TransferStatus::PENDING) {
            throw InvalidTransferException::invalidStatus($transferId, $status->value, TransferStatus::PENDING->value);
        }

        // Mark as rejected
        $this->transferRepository->markAsRejected($transferId, $rejectedBy, $reason);
        
        return $this->transferRepository->findById($transferId);
    }

    public function cancelTransfer(string $transferId): bool
    {
        $transfer = $this->transferRepository->findById($transferId);
        if (!$transfer) {
            throw new TransferNotFoundException($transferId);
        }

        // Only pending transfers can be cancelled
        $status = TransferStatus::from($transfer->getStatus());
        if ($status !== TransferStatus::PENDING) {
            throw InvalidTransferException::invalidStatus($transferId, $status->value, TransferStatus::PENDING->value);
        }

        return $this->transferRepository->delete($transferId);
    }

    public function completeTransfer(string $transferId): TransferInterface
    {
        $transfer = $this->transferRepository->findById($transferId);
        if (!$transfer) {
            throw new TransferNotFoundException($transferId);
        }

        // Must be approved to complete
        $status = TransferStatus::from($transfer->getStatus());
        if ($status !== TransferStatus::APPROVED) {
            throw InvalidTransferException::invalidStatus($transferId, $status->value, TransferStatus::APPROVED->value);
        }

        // Check if effective date has passed
        $effectiveDate = $transfer->getEffectiveDate();
        $now = new \DateTime();
        if ($effectiveDate > $now) {
            throw new \InvalidArgumentException('Transfer effective date has not been reached yet');
        }

        // Mark as completed
        $this->transferRepository->markAsCompleted($transferId);
        
        // Execute actual staff reassignment (delegated to application layer)
        // This would update staff department/office assignments
        
        return $this->transferRepository->findById($transferId);
    }

    public function rollbackTransfer(string $transferId): TransferInterface
    {
        $transfer = $this->transferRepository->findById($transferId);
        if (!$transfer) {
            throw new TransferNotFoundException($transferId);
        }

        // Only completed transfers can be rolled back
        $status = TransferStatus::from($transfer->getStatus());
        if ($status !== TransferStatus::COMPLETED) {
            throw InvalidTransferException::invalidStatus($transferId, $status->value, TransferStatus::COMPLETED->value);
        }

        // Mark as cancelled (rollback is essentially cancelling a completed transfer)
        $updatedData = ['status' => TransferStatus::CANCELLED->value];
        $this->transferRepository->update($transferId, $updatedData);
        
        // Restore previous assignments (delegated to application layer)
        
        return $this->transferRepository->findById($transferId);
    }

    public function getTransfer(string $transferId): ?TransferInterface
    {
        return $this->transferRepository->findById($transferId);
    }

    public function getPendingTransfers(): array
    {
        return $this->transferRepository->getPendingTransfers();
    }

    public function getStaffTransferHistory(string $staffId): array
    {
        return $this->transferRepository->getByStaff($staffId);
    }
}
