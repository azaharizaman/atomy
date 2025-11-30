<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Services;

use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Contracts\TransferInterface;
use Nexus\Backoffice\Contracts\TransferRepositoryInterface;
use Nexus\Backoffice\Exceptions\InvalidTransferException;
use Nexus\Backoffice\Exceptions\StaffNotFoundException;
use Nexus\Backoffice\Exceptions\TransferNotFoundException;
use Nexus\Backoffice\Services\TransferManager;
use Nexus\Backoffice\ValueObjects\StaffStatus;
use Nexus\Backoffice\ValueObjects\TransferStatus;
use Nexus\Backoffice\ValueObjects\TransferType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TransferManager service.
 */
class TransferManagerTest extends TestCase
{
    private TransferRepositoryInterface $transferRepository;
    private StaffRepositoryInterface $staffRepository;
    private TransferManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transferRepository = $this->createMock(TransferRepositoryInterface::class);
        $this->staffRepository = $this->createMock(StaffRepositoryInterface::class);

        $this->manager = new TransferManager(
            $this->transferRepository,
            $this->staffRepository
        );
    }

    // =========================================================================
    // Create Transfer Request Tests
    // =========================================================================

    public function test_create_transfer_request_with_valid_data(): void
    {
        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');
        $staff->method('getStatus')->willReturn(StaffStatus::ACTIVE->value);

        $transferData = [
            'staff_id' => 'staff-123',
            'from_department_id' => 'dept-001',
            'to_department_id' => 'dept-002',
            'effective_date' => (new \DateTimeImmutable('+7 days'))->format('Y-m-d'),
            'transfer_type' => TransferType::LATERAL_MOVE->value,
            'reason' => 'Career development',
        ];

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStaffId')->willReturn('staff-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::PENDING->value);

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        // The service uses getPendingByStaff() and checks count
        $this->transferRepository
            ->method('getPendingByStaff')
            ->with('staff-123')
            ->willReturn([]);

        $this->transferRepository
            ->method('save')
            ->willReturn($transfer);

        $result = $this->manager->createTransferRequest($transferData);

        $this->assertInstanceOf(TransferInterface::class, $result);
        $this->assertSame('transfer-123', $result->getId());
        $this->assertSame(TransferStatus::PENDING->value, $result->getStatus());
    }

    public function test_create_transfer_request_throws_exception_when_staff_not_found(): void
    {
        $this->staffRepository
            ->method('findById')
            ->with('nonexistent-staff')
            ->willReturn(null);

        $this->expectException(StaffNotFoundException::class);

        $this->manager->createTransferRequest([
            'staff_id' => 'nonexistent-staff',
            'transfer_type' => TransferType::LATERAL_MOVE->value,
            'effective_date' => (new \DateTimeImmutable('+7 days'))->format('Y-m-d'),
        ]);
    }

    public function test_create_transfer_request_throws_exception_when_pending_transfer_exists(): void
    {
        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');
        $staff->method('getStatus')->willReturn(StaffStatus::ACTIVE->value);

        $existingTransfer = $this->createMock(TransferInterface::class);
        $existingTransfer->method('getId')->willReturn('existing-transfer');

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        // Return an array with one pending transfer
        $this->transferRepository
            ->method('getPendingByStaff')
            ->with('staff-123')
            ->willReturn([$existingTransfer]);

        $this->expectException(InvalidTransferException::class);

        $this->manager->createTransferRequest([
            'staff_id' => 'staff-123',
            'transfer_type' => TransferType::LATERAL_MOVE->value,
            'effective_date' => (new \DateTimeImmutable('+7 days'))->format('Y-m-d'),
        ]);
    }

    public function test_create_transfer_request_throws_exception_for_retroactive_date(): void
    {
        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');
        $staff->method('getStatus')->willReturn(StaffStatus::ACTIVE->value);

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        // No pending transfers
        $this->transferRepository
            ->method('getPendingByStaff')
            ->with('staff-123')
            ->willReturn([]);

        $this->expectException(InvalidTransferException::class);

        $this->manager->createTransferRequest([
            'staff_id' => 'staff-123',
            'transfer_type' => TransferType::LATERAL_MOVE->value,
            'effective_date' => (new \DateTimeImmutable('-60 days'))->format('Y-m-d'),
        ]);
    }

    // =========================================================================
    // Approve Transfer Tests
    // =========================================================================

    public function test_approve_transfer_with_pending_status(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::PENDING->value);

        $approvedTransfer = $this->createMock(TransferInterface::class);
        $approvedTransfer->method('getId')->willReturn('transfer-123');
        $approvedTransfer->method('getStatus')->willReturn(TransferStatus::APPROVED->value);

        // First call returns pending, second call returns approved
        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturnOnConsecutiveCalls($transfer, $approvedTransfer);

        // The service calls markAsApproved instead of update
        $this->transferRepository
            ->expects($this->once())
            ->method('markAsApproved')
            ->with('transfer-123', 'approver-001', 'Approved for transfer');

        $result = $this->manager->approveTransfer('transfer-123', 'approver-001', 'Approved for transfer');

        $this->assertSame(TransferStatus::APPROVED->value, $result->getStatus());
    }

    public function test_approve_transfer_throws_exception_when_not_pending(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::COMPLETED->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->expectException(InvalidTransferException::class);

        $this->manager->approveTransfer('transfer-123', 'approver-001', 'Comment');
    }

    public function test_approve_transfer_throws_exception_when_not_found(): void
    {
        $this->transferRepository
            ->method('findById')
            ->with('nonexistent-id')
            ->willReturn(null);

        $this->expectException(TransferNotFoundException::class);

        $this->manager->approveTransfer('nonexistent-id', 'approver-001', 'Comment');
    }

    // =========================================================================
    // Reject Transfer Tests
    // =========================================================================

    public function test_reject_transfer_with_pending_status(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::PENDING->value);

        $rejectedTransfer = $this->createMock(TransferInterface::class);
        $rejectedTransfer->method('getId')->willReturn('transfer-123');
        $rejectedTransfer->method('getStatus')->willReturn(TransferStatus::REJECTED->value);

        // First call returns pending, second call returns rejected
        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturnOnConsecutiveCalls($transfer, $rejectedTransfer);

        // The service calls markAsRejected
        $this->transferRepository
            ->expects($this->once())
            ->method('markAsRejected')
            ->with('transfer-123', 'approver-001', 'Budget constraints');

        $result = $this->manager->rejectTransfer('transfer-123', 'approver-001', 'Budget constraints');

        $this->assertSame(TransferStatus::REJECTED->value, $result->getStatus());
    }

    public function test_reject_transfer_throws_exception_when_not_pending(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::APPROVED->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->expectException(InvalidTransferException::class);

        $this->manager->rejectTransfer('transfer-123', 'approver-001', 'Reason');
    }

    // =========================================================================
    // Cancel Transfer Tests
    // =========================================================================

    public function test_cancel_pending_transfer(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::PENDING->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        // cancelTransfer calls delete() and returns bool
        $this->transferRepository
            ->expects($this->once())
            ->method('delete')
            ->with('transfer-123')
            ->willReturn(true);

        // The service method returns bool, not TransferInterface
        $result = $this->manager->cancelTransfer('transfer-123');

        $this->assertTrue($result);
    }

    public function test_cancel_transfer_throws_exception_when_not_pending(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        // Already approved, cannot cancel
        $transfer->method('getStatus')->willReturn(TransferStatus::APPROVED->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->expectException(InvalidTransferException::class);

        $this->manager->cancelTransfer('transfer-123');
    }

    public function test_cancel_transfer_throws_exception_when_completed(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::COMPLETED->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->expectException(InvalidTransferException::class);

        $this->manager->cancelTransfer('transfer-123');
    }

    // =========================================================================
    // Complete Transfer Tests
    // =========================================================================

    public function test_complete_approved_transfer(): void
    {
        $effectiveDate = new \DateTimeImmutable('-1 day'); // In the past so it can be completed
        
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStaffId')->willReturn('staff-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::APPROVED->value);
        $transfer->method('getEffectiveDate')->willReturn($effectiveDate);

        $completedTransfer = $this->createMock(TransferInterface::class);
        $completedTransfer->method('getId')->willReturn('transfer-123');
        $completedTransfer->method('getStatus')->willReturn(TransferStatus::COMPLETED->value);

        // First call returns approved, second call returns completed
        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturnOnConsecutiveCalls($transfer, $completedTransfer);

        // The service calls markAsCompleted
        $this->transferRepository
            ->expects($this->once())
            ->method('markAsCompleted')
            ->with('transfer-123');

        $result = $this->manager->completeTransfer('transfer-123');

        $this->assertSame(TransferStatus::COMPLETED->value, $result->getStatus());
    }

    public function test_complete_transfer_throws_exception_when_not_approved(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::PENDING->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->expectException(InvalidTransferException::class);

        $this->manager->completeTransfer('transfer-123');
    }

    // =========================================================================
    // Rollback Transfer Tests
    // =========================================================================

    public function test_rollback_completed_transfer(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStaffId')->willReturn('staff-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::COMPLETED->value);

        $cancelledTransfer = $this->createMock(TransferInterface::class);
        $cancelledTransfer->method('getId')->willReturn('transfer-123');
        $cancelledTransfer->method('getStatus')->willReturn(TransferStatus::CANCELLED->value);

        // First call returns completed, second call returns cancelled
        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturnOnConsecutiveCalls($transfer, $cancelledTransfer);

        // The service calls update with new status
        $this->transferRepository
            ->expects($this->once())
            ->method('update')
            ->with('transfer-123', ['status' => TransferStatus::CANCELLED->value])
            ->willReturn($cancelledTransfer);

        // rollbackTransfer takes only 1 parameter (no reason parameter)
        $result = $this->manager->rollbackTransfer('transfer-123');

        $this->assertSame(TransferStatus::CANCELLED->value, $result->getStatus());
    }

    public function test_rollback_transfer_throws_exception_when_not_completed(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::APPROVED->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->expectException(InvalidTransferException::class);

        // rollbackTransfer takes only 1 parameter
        $this->manager->rollbackTransfer('transfer-123');
    }

    // =========================================================================
    // Query Method Tests
    // =========================================================================

    public function test_get_transfer_returns_transfer(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $result = $this->manager->getTransfer('transfer-123');

        $this->assertInstanceOf(TransferInterface::class, $result);
        $this->assertSame('transfer-123', $result->getId());
    }

    public function test_get_transfer_returns_null_when_not_found(): void
    {
        $this->transferRepository
            ->method('findById')
            ->with('nonexistent-id')
            ->willReturn(null);

        $result = $this->manager->getTransfer('nonexistent-id');

        $this->assertNull($result);
    }

    public function test_get_pending_transfers(): void
    {
        $transfer1 = $this->createMock(TransferInterface::class);
        $transfer1->method('getId')->willReturn('transfer-1');
        
        $transfer2 = $this->createMock(TransferInterface::class);
        $transfer2->method('getId')->willReturn('transfer-2');

        $this->transferRepository
            ->method('getPendingTransfers')
            ->willReturn([$transfer1, $transfer2]);

        $result = $this->manager->getPendingTransfers();

        $this->assertCount(2, $result);
        $this->assertSame('transfer-1', $result[0]->getId());
        $this->assertSame('transfer-2', $result[1]->getId());
    }

    public function test_get_staff_transfer_history(): void
    {
        $transfer1 = $this->createMock(TransferInterface::class);
        $transfer1->method('getId')->willReturn('transfer-1');
        $transfer1->method('getStatus')->willReturn(TransferStatus::COMPLETED->value);
        
        $transfer2 = $this->createMock(TransferInterface::class);
        $transfer2->method('getId')->willReturn('transfer-2');
        $transfer2->method('getStatus')->willReturn(TransferStatus::PENDING->value);

        $this->transferRepository
            ->method('getByStaff')
            ->with('staff-123')
            ->willReturn([$transfer1, $transfer2]);

        $result = $this->manager->getStaffTransferHistory('staff-123');

        $this->assertCount(2, $result);
        $this->assertSame('transfer-1', $result[0]->getId());
        $this->assertSame('transfer-2', $result[1]->getId());
    }
}
