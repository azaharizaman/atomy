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
use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\StaffStatus;
use Nexus\Backoffice\ValueObjects\TransferStatus;

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
            'type' => 'lateral_move',
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

        $this->transferRepository
            ->method('hasPendingTransfer')
            ->with('staff-123')
            ->willReturn(false);

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
            'from_department_id' => 'dept-001',
            'to_department_id' => 'dept-002',
        ]);
    }

    public function test_create_transfer_request_throws_exception_when_pending_transfer_exists(): void
    {
        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');
        $staff->method('getStatus')->willReturn(StaffStatus::ACTIVE->value);

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        $this->transferRepository
            ->method('hasPendingTransfer')
            ->with('staff-123')
            ->willReturn(true);

        $this->expectException(InvalidTransferException::class);

        $this->manager->createTransferRequest([
            'staff_id' => 'staff-123',
            'from_department_id' => 'dept-001',
            'to_department_id' => 'dept-002',
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

        $this->transferRepository
            ->method('hasPendingTransfer')
            ->with('staff-123')
            ->willReturn(false);

        $this->expectException(InvalidTransferException::class);

        $this->manager->createTransferRequest([
            'staff_id' => 'staff-123',
            'from_department_id' => 'dept-001',
            'to_department_id' => 'dept-002',
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

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->transferRepository
            ->method('update')
            ->willReturn($approvedTransfer);

        $result = $this->manager->approveTransfer('transfer-123', 'approver-001');

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

        $this->manager->approveTransfer('transfer-123', 'approver-001');
    }

    public function test_approve_transfer_throws_exception_when_not_found(): void
    {
        $this->transferRepository
            ->method('findById')
            ->with('nonexistent-id')
            ->willReturn(null);

        $this->expectException(TransferNotFoundException::class);

        $this->manager->approveTransfer('nonexistent-id', 'approver-001');
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

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->transferRepository
            ->method('update')
            ->willReturn($rejectedTransfer);

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

        $cancelledTransfer = $this->createMock(TransferInterface::class);
        $cancelledTransfer->method('getId')->willReturn('transfer-123');
        $cancelledTransfer->method('getStatus')->willReturn(TransferStatus::CANCELLED->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->transferRepository
            ->method('update')
            ->willReturn($cancelledTransfer);

        $result = $this->manager->cancelTransfer('transfer-123', 'Employee request');

        $this->assertSame(TransferStatus::CANCELLED->value, $result->getStatus());
    }

    public function test_cancel_approved_transfer(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::APPROVED->value);

        $cancelledTransfer = $this->createMock(TransferInterface::class);
        $cancelledTransfer->method('getId')->willReturn('transfer-123');
        $cancelledTransfer->method('getStatus')->willReturn(TransferStatus::CANCELLED->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->transferRepository
            ->method('update')
            ->willReturn($cancelledTransfer);

        $result = $this->manager->cancelTransfer('transfer-123', 'Changed requirements');

        $this->assertSame(TransferStatus::CANCELLED->value, $result->getStatus());
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

        $this->manager->cancelTransfer('transfer-123', 'Reason');
    }

    // =========================================================================
    // Complete Transfer Tests
    // =========================================================================

    public function test_complete_approved_transfer(): void
    {
        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getId')->willReturn('transfer-123');
        $transfer->method('getStaffId')->willReturn('staff-123');
        $transfer->method('getStatus')->willReturn(TransferStatus::APPROVED->value);
        $transfer->method('getToDepartmentId')->willReturn('dept-002');
        $transfer->method('getToOfficeId')->willReturn('office-002');

        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');

        $completedTransfer = $this->createMock(TransferInterface::class);
        $completedTransfer->method('getId')->willReturn('transfer-123');
        $completedTransfer->method('getStatus')->willReturn(TransferStatus::COMPLETED->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        $this->staffRepository
            ->method('update')
            ->willReturn($staff);

        $this->transferRepository
            ->method('update')
            ->willReturn($completedTransfer);

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
        $transfer->method('getFromDepartmentId')->willReturn('dept-001');
        $transfer->method('getFromOfficeId')->willReturn('office-001');

        $staff = $this->createMock(StaffInterface::class);
        $staff->method('getId')->willReturn('staff-123');

        $cancelledTransfer = $this->createMock(TransferInterface::class);
        $cancelledTransfer->method('getId')->willReturn('transfer-123');
        $cancelledTransfer->method('getStatus')->willReturn(TransferStatus::CANCELLED->value);

        $this->transferRepository
            ->method('findById')
            ->with('transfer-123')
            ->willReturn($transfer);

        $this->staffRepository
            ->method('findById')
            ->with('staff-123')
            ->willReturn($staff);

        $this->staffRepository
            ->method('update')
            ->willReturn($staff);

        $this->transferRepository
            ->method('update')
            ->willReturn($cancelledTransfer);

        $result = $this->manager->rollbackTransfer('transfer-123', 'Data entry error');

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

        $this->manager->rollbackTransfer('transfer-123', 'Reason');
    }
}
