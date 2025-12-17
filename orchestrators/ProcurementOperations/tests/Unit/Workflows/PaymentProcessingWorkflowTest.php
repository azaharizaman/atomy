<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Workflows;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\BankFileGenerationResult;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchApprovedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchCreatedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchProcessedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchRejectedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchSubmittedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentItemFailedEvent;
use Nexus\ProcurementOperations\Workflows\PaymentProcessingWorkflow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

#[CoversClass(PaymentProcessingWorkflow::class)]
final class PaymentProcessingWorkflowTest extends TestCase
{
    private PaymentProcessingWorkflow $workflow;
    private EventDispatcherInterface $eventDispatcher;
    private array $dispatchedEvents = [];

    protected function setUp(): void
    {
        $this->dispatchedEvents = [];
        
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(function (object $event) {
                $this->dispatchedEvents[] = $event;
                return $event;
            });

        $this->workflow = new PaymentProcessingWorkflow(
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function it_starts_new_payment_batch(): void
    {
        $batchData = $this->workflow->start(
            tenantId: 'tenant-001',
            batchReference: 'BATCH-2024-001',
            paymentMethod: 'ACH',
            description: 'Monthly vendor payments',
            createdBy: 'user-001',
        );

        $this->assertSame('DRAFT', $batchData->status);
        $this->assertSame('tenant-001', $batchData->tenantId);
        $this->assertSame('BATCH-2024-001', $batchData->batchReference);
        $this->assertSame('ACH', $batchData->paymentMethod);
        $this->assertSame('Monthly vendor payments', $batchData->description);
        $this->assertSame('user-001', $batchData->createdBy);
        $this->assertEmpty($batchData->paymentItems);
        
        $this->assertCount(1, $this->dispatchedEvents);
        $this->assertInstanceOf(PaymentBatchCreatedEvent::class, $this->dispatchedEvents[0]);
    }

    #[Test]
    public function it_adds_payment_items_to_batch(): void
    {
        $batchData = $this->workflow->start(
            tenantId: 'tenant-001',
            batchReference: 'BATCH-2024-001',
            paymentMethod: 'ACH',
            description: 'Monthly vendor payments',
            createdBy: 'user-001',
        );

        $updatedBatch = $this->workflow->addPaymentItem(
            batch: $batchData,
            vendorId: 'vendor-001',
            vendorName: 'Test Vendor',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001', 'inv-002'],
            bankAccount: '123456789',
            bankRoutingNumber: '021000021',
        );

        $this->assertCount(1, $updatedBatch->paymentItems);
        $this->assertSame(5000.0, $updatedBatch->totalAmount->getAmount());
        
        $paymentItem = $updatedBatch->paymentItems[0];
        $this->assertSame('vendor-001', $paymentItem->vendorId);
        $this->assertSame('Test Vendor', $paymentItem->vendorName);
        $this->assertSame('PENDING', $paymentItem->status);
    }

    #[Test]
    public function it_adds_multiple_payment_items(): void
    {
        $batchData = $this->workflow->start(
            tenantId: 'tenant-001',
            batchReference: 'BATCH-2024-001',
            paymentMethod: 'ACH',
            description: 'Monthly vendor payments',
            createdBy: 'user-001',
        );

        $batchData = $this->workflow->addPaymentItem(
            batch: $batchData,
            vendorId: 'vendor-001',
            vendorName: 'Vendor One',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            bankAccount: '111111111',
            bankRoutingNumber: '021000021',
        );

        $batchData = $this->workflow->addPaymentItem(
            batch: $batchData,
            vendorId: 'vendor-002',
            vendorName: 'Vendor Two',
            amount: Money::of(3000.00, 'USD'),
            invoiceIds: ['inv-002'],
            bankAccount: '222222222',
            bankRoutingNumber: '021000021',
        );

        $this->assertCount(2, $batchData->paymentItems);
        $this->assertSame(8000.0, $batchData->totalAmount->getAmount());
    }

    #[Test]
    public function it_submits_batch_for_approval(): void
    {
        $batchData = $this->createBatchWithItems();
        
        $submitted = $this->workflow->submitForApproval(
            batch: $batchData,
            submittedBy: 'user-001',
            requiredApprovalLevel: 2,
        );

        $this->assertSame('PENDING_APPROVAL', $submitted->status);
        $this->assertSame('user-001', $submitted->submittedBy);
        $this->assertSame(2, $submitted->requiredApprovalLevel);
        $this->assertNotNull($submitted->submittedAt);
        
        $this->assertInstanceOf(PaymentBatchSubmittedEvent::class, end($this->dispatchedEvents));
    }

    #[Test]
    public function it_approves_batch(): void
    {
        $batchData = $this->createBatchWithItems();
        $submitted = $this->workflow->submitForApproval($batchData, 'user-001', 1);
        
        $approved = $this->workflow->approve(
            batch: $submitted,
            approvedBy: 'manager-001',
            approvalLevel: 1,
            approvalNote: 'Approved for payment',
        );

        $this->assertSame('APPROVED', $approved->status);
        $this->assertSame('manager-001', $approved->approvedBy);
        $this->assertNotNull($approved->approvedAt);
        $this->assertCount(1, $approved->approvalChain);
        
        $approval = $approved->approvalChain[0];
        $this->assertSame('manager-001', $approval['approved_by']);
        $this->assertSame(1, $approval['level']);
        $this->assertSame('Approved for payment', $approval['note']);
        
        $this->assertInstanceOf(PaymentBatchApprovedEvent::class, end($this->dispatchedEvents));
    }

    #[Test]
    public function it_rejects_batch(): void
    {
        $batchData = $this->createBatchWithItems();
        $submitted = $this->workflow->submitForApproval($batchData, 'user-001', 1);
        
        $rejected = $this->workflow->reject(
            batch: $submitted,
            rejectedBy: 'manager-001',
            rejectionReason: 'Missing documentation',
            rejectionCode: 'MISSING_DOCS',
        );

        $this->assertSame('REJECTED', $rejected->status);
        $this->assertSame('manager-001', $rejected->rejectedBy);
        $this->assertSame('Missing documentation', $rejected->rejectionReason);
        $this->assertSame('MISSING_DOCS', $rejected->rejectionCode);
        $this->assertNotNull($rejected->rejectedAt);
        
        $this->assertInstanceOf(PaymentBatchRejectedEvent::class, end($this->dispatchedEvents));
    }

    #[Test]
    public function it_processes_approved_batch(): void
    {
        $batchData = $this->createBatchWithItems();
        $submitted = $this->workflow->submitForApproval($batchData, 'user-001', 1);
        $approved = $this->workflow->approve($submitted, 'manager-001', 1, 'Approved');
        
        $bankFileResult = BankFileGenerationResult::nachaFile(
            fileName: 'payment_20240120.ach',
            fileContent: 'NACHA content...',
            totalAmount: Money::of(8000.00, 'USD'),
            recordCount: 2,
            batchCount: 1,
            successfulPayments: ['pmt-001', 'pmt-002'],
        );
        
        $processed = $this->workflow->process(
            batch: $approved,
            bankFileGenerator: fn(PaymentBatchData $batch) => $bankFileResult,
        );

        $this->assertSame('PROCESSING', $processed->status);
        $this->assertSame('payment_20240120.ach', $processed->bankFileName);
        $this->assertNotNull($processed->processedAt);
        
        $this->assertInstanceOf(PaymentBatchProcessedEvent::class, end($this->dispatchedEvents));
    }

    #[Test]
    public function it_completes_batch(): void
    {
        $batchData = $this->createBatchWithItems();
        $submitted = $this->workflow->submitForApproval($batchData, 'user-001', 1);
        $approved = $this->workflow->approve($submitted, 'manager-001', 1, 'Approved');
        
        $bankFileResult = BankFileGenerationResult::nachaFile(
            fileName: 'payment_20240120.ach',
            fileContent: 'NACHA content...',
            totalAmount: Money::of(8000.00, 'USD'),
            recordCount: 2,
            batchCount: 1,
            successfulPayments: ['pmt-001', 'pmt-002'],
        );
        
        $processed = $this->workflow->process(
            batch: $approved,
            bankFileGenerator: fn(PaymentBatchData $batch) => $bankFileResult,
        );

        $completed = $this->workflow->complete($processed);

        $this->assertSame('COMPLETED', $completed->status);
        $this->assertNotNull($completed->completedAt);
    }

    #[Test]
    public function it_records_item_failure(): void
    {
        $batchData = $this->createBatchWithItems();
        $submitted = $this->workflow->submitForApproval($batchData, 'user-001', 1);
        $approved = $this->workflow->approve($submitted, 'manager-001', 1, 'Approved');
        
        $bankFileResult = BankFileGenerationResult::nachaFile(
            fileName: 'payment_20240120.ach',
            fileContent: 'NACHA content...',
            totalAmount: Money::of(8000.00, 'USD'),
            recordCount: 2,
            batchCount: 1,
            successfulPayments: [],
        );
        
        $processed = $this->workflow->process(
            batch: $approved,
            bankFileGenerator: fn(PaymentBatchData $batch) => $bankFileResult,
        );

        $paymentItemId = $processed->paymentItems[0]->paymentItemId;
        
        $withFailure = $this->workflow->recordItemFailure(
            batch: $processed,
            paymentItemId: $paymentItemId,
            failureCode: 'INVALID_ACCOUNT',
            failureReason: 'Bank account is invalid',
            isRetryable: false,
        );

        $failedItem = array_values(array_filter(
            $withFailure->paymentItems,
            fn($item) => $item->paymentItemId === $paymentItemId
        ))[0];

        $this->assertSame('FAILED', $failedItem->status);
        $this->assertSame('INVALID_ACCOUNT', $failedItem->failureCode);
        
        $this->assertInstanceOf(PaymentItemFailedEvent::class, end($this->dispatchedEvents));
    }

    #[Test]
    public function it_cancels_draft_batch(): void
    {
        $batchData = $this->workflow->start(
            tenantId: 'tenant-001',
            batchReference: 'BATCH-2024-001',
            paymentMethod: 'ACH',
            description: 'Monthly vendor payments',
            createdBy: 'user-001',
        );

        $cancelled = $this->workflow->cancel(
            batch: $batchData,
            cancelledBy: 'user-001',
            reason: 'No longer needed',
        );

        $this->assertSame('CANCELLED', $cancelled->status);
    }

    #[Test]
    public function it_exports_and_restores_workflow_state(): void
    {
        $batchData = $this->createBatchWithItems();
        $submitted = $this->workflow->submitForApproval($batchData, 'user-001', 2);
        
        $state = $this->workflow->exportState($submitted);

        $this->assertArrayHasKey('batch_id', $state);
        $this->assertArrayHasKey('tenant_id', $state);
        $this->assertArrayHasKey('status', $state);
        $this->assertArrayHasKey('payment_items', $state);
        
        $restored = $this->workflow->restoreState($state);
        
        $this->assertSame($submitted->batchId, $restored->batchId);
        $this->assertSame($submitted->status, $restored->status);
        $this->assertSame($submitted->totalAmount->getAmount(), $restored->totalAmount->getAmount());
    }

    #[Test]
    public function it_throws_exception_for_invalid_state_transition(): void
    {
        $batchData = $this->workflow->start(
            tenantId: 'tenant-001',
            batchReference: 'BATCH-2024-001',
            paymentMethod: 'ACH',
            description: 'Monthly vendor payments',
            createdBy: 'user-001',
        );

        // Cannot approve a DRAFT batch (must be PENDING_APPROVAL first)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot approve batch');
        
        $this->workflow->approve($batchData, 'manager-001', 1, 'Approved');
    }

    #[Test]
    public function it_requires_all_approvals_for_multi_level(): void
    {
        $batchData = $this->createBatchWithItems();
        $submitted = $this->workflow->submitForApproval($batchData, 'user-001', 2);
        
        // First level approval
        $partiallyApproved = $this->workflow->approve(
            batch: $submitted,
            approvedBy: 'manager-001',
            approvalLevel: 1,
            approvalNote: 'Level 1 approved',
        );
        
        // Should still be PENDING_APPROVAL as level 2 needed
        $this->assertSame('PENDING_APPROVAL', $partiallyApproved->status);
        $this->assertCount(1, $partiallyApproved->approvalChain);
        
        // Second level approval
        $fullyApproved = $this->workflow->approve(
            batch: $partiallyApproved,
            approvedBy: 'director-001',
            approvalLevel: 2,
            approvalNote: 'Level 2 approved',
        );
        
        $this->assertSame('APPROVED', $fullyApproved->status);
        $this->assertCount(2, $fullyApproved->approvalChain);
    }

    #[Test]
    public function it_gets_pending_approval_count(): void
    {
        $batchData = $this->createBatchWithItems();
        $submitted = $this->workflow->submitForApproval($batchData, 'user-001', 3);
        
        $this->assertSame(3, $this->workflow->getPendingApprovalCount($submitted));
        
        $afterLevel1 = $this->workflow->approve($submitted, 'manager-001', 1, 'Level 1');
        $this->assertSame(2, $this->workflow->getPendingApprovalCount($afterLevel1));
        
        $afterLevel2 = $this->workflow->approve($afterLevel1, 'director-001', 2, 'Level 2');
        $this->assertSame(1, $this->workflow->getPendingApprovalCount($afterLevel2));
    }

    #[Test]
    public function it_validates_batch_has_payment_items_before_submit(): void
    {
        $batchData = $this->workflow->start(
            tenantId: 'tenant-001',
            batchReference: 'BATCH-2024-001',
            paymentMethod: 'ACH',
            description: 'Empty batch',
            createdBy: 'user-001',
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot submit empty batch');
        
        $this->workflow->submitForApproval($batchData, 'user-001', 1);
    }

    // Helper method to create a batch with items
    private function createBatchWithItems(): PaymentBatchData
    {
        $batchData = $this->workflow->start(
            tenantId: 'tenant-001',
            batchReference: 'BATCH-2024-001',
            paymentMethod: 'ACH',
            description: 'Test batch',
            createdBy: 'user-001',
        );

        $batchData = $this->workflow->addPaymentItem(
            batch: $batchData,
            vendorId: 'vendor-001',
            vendorName: 'Vendor One',
            amount: Money::of(5000.00, 'USD'),
            invoiceIds: ['inv-001'],
            bankAccount: '111111111',
            bankRoutingNumber: '021000021',
        );

        return $this->workflow->addPaymentItem(
            batch: $batchData,
            vendorId: 'vendor-002',
            vendorName: 'Vendor Two',
            amount: Money::of(3000.00, 'USD'),
            invoiceIds: ['inv-002'],
            bankAccount: '222222222',
            bankRoutingNumber: '021000021',
        );
    }
}
