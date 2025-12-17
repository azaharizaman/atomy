<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Enums\PaymentBatchStatus;
use Nexus\ProcurementOperations\Events\Financial\PaymentBatchApprovedEvent;
use Nexus\ProcurementOperations\Events\Financial\PaymentBatchCreatedEvent;
use Nexus\ProcurementOperations\Events\Financial\PaymentBatchProcessedEvent;
use Nexus\ProcurementOperations\Events\Financial\PaymentBatchRejectedEvent;
use Nexus\ProcurementOperations\Events\Financial\PaymentBatchSubmittedEvent;
use Nexus\ProcurementOperations\Services\PaymentProcessingService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(PaymentProcessingService::class)]
final class PaymentProcessingServiceTest extends TestCase
{
    private PaymentProcessingService $service;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new PaymentProcessingService(
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function it_creates_payment_batch(): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(PaymentBatchCreatedEvent::class));

        $batch = $this->service->createBatch(
            batchName: 'Weekly AP Payment Run',
            paymentMethod: 'ACH',
            paymentDate: new \DateTimeImmutable('+3 days'),
            currency: 'USD',
            createdBy: 'USER-001',
        );

        $this->assertInstanceOf(PaymentBatchData::class, $batch);
        $this->assertNotEmpty($batch->batchId);
        $this->assertEquals('Weekly AP Payment Run', $batch->batchName);
        $this->assertEquals('ACH', $batch->paymentMethod);
        $this->assertEquals(PaymentBatchStatus::DRAFT, $batch->status);
        $this->assertEquals(0, $batch->itemCount);
        $this->assertEquals(0.00, $batch->totalAmount->getAmount());
    }

    #[Test]
    public function it_adds_payment_item_to_batch(): void
    {
        // Create batch first
        $batch = $this->service->createBatch(
            batchName: 'Test Batch',
            paymentMethod: 'ACH',
            paymentDate: new \DateTimeImmutable('+3 days'),
            currency: 'USD',
            createdBy: 'USER-001',
        );

        // Add item
        $updatedBatch = $this->service->addPaymentItem(
            batchId: $batch->batchId,
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Acme Corporation',
            paymentAmount: Money::of(5000.00, 'USD'),
            invoiceNumber: 'INV-2024-001',
            routingNumber: '123456789',
            bankAccountNumber: '9876543210',
        );

        $this->assertEquals(1, $updatedBatch->itemCount);
        $this->assertEquals(5000.00, $updatedBatch->totalAmount->getAmount());
        $this->assertCount(1, $updatedBatch->items);

        $item = $updatedBatch->items[0];
        $this->assertEquals('INV-001', $item->invoiceId);
        $this->assertEquals('VENDOR-001', $item->vendorId);
        $this->assertEquals(5000.00, $item->paymentAmount->getAmount());
    }

    #[Test]
    public function it_validates_batch_before_submission(): void
    {
        // Create batch with items
        $batch = $this->service->createBatch(
            batchName: 'Valid Batch',
            paymentMethod: 'ACH',
            paymentDate: new \DateTimeImmutable('+3 days'),
            currency: 'USD',
            createdBy: 'USER-001',
        );

        $batch = $this->service->addPaymentItem(
            batchId: $batch->batchId,
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Vendor One',
            paymentAmount: Money::of(1000.00, 'USD'),
            invoiceNumber: 'INV-2024-001',
            routingNumber: '123456789',
            bankAccountNumber: '1111111111',
        );

        $validation = $this->service->validateBatch(batchId: $batch->batchId);

        $this->assertTrue($validation['is_valid']);
        $this->assertEmpty($validation['errors']);
    }

    #[Test]
    public function it_fails_validation_for_empty_batch(): void
    {
        $batch = $this->service->createBatch(
            batchName: 'Empty Batch',
            paymentMethod: 'ACH',
            paymentDate: new \DateTimeImmutable('+3 days'),
            currency: 'USD',
            createdBy: 'USER-001',
        );

        $validation = $this->service->validateBatch(batchId: $batch->batchId);

        $this->assertFalse($validation['is_valid']);
        $this->assertContains('Batch must contain at least one payment item', $validation['errors']);
    }

    #[Test]
    public function it_submits_batch_for_approval(): void
    {
        $this->eventDispatcher
            ->expects($this->exactly(2)) // Create + Submit
            ->method('dispatch');

        $batch = $this->createBatchWithItems();

        $submitted = $this->service->submitForApproval(
            batchId: $batch->batchId,
            submittedBy: 'USER-001',
        );

        $this->assertEquals(PaymentBatchStatus::PENDING_APPROVAL, $submitted->status);
    }

    #[Test]
    public function it_approves_batch_with_sufficient_authority(): void
    {
        $this->eventDispatcher
            ->expects($this->exactly(3)) // Create + Submit + Approve
            ->method('dispatch');

        $batch = $this->createBatchWithItems();
        $submitted = $this->service->submitForApproval($batch->batchId, 'USER-001');

        // User with sufficient approval authority
        $approved = $this->service->approve(
            batchId: $submitted->batchId,
            approverId: 'APPROVER-001',
            approverLevel: 3, // High approval authority
        );

        $this->assertEquals(PaymentBatchStatus::APPROVED, $approved->status);
        $this->assertEquals('APPROVER-001', $approved->approvedBy);
        $this->assertInstanceOf(\DateTimeImmutable::class, $approved->approvedAt);
    }

    #[Test]
    public function it_rejects_batch_with_reason(): void
    {
        $this->eventDispatcher
            ->expects($this->exactly(3)) // Create + Submit + Reject
            ->method('dispatch');

        $batch = $this->createBatchWithItems();
        $submitted = $this->service->submitForApproval($batch->batchId, 'USER-001');

        $rejected = $this->service->reject(
            batchId: $submitted->batchId,
            rejectedBy: 'APPROVER-001',
            rejectionReason: 'Duplicate payment detected for INV-001',
        );

        $this->assertEquals(PaymentBatchStatus::REJECTED, $rejected->status);
        $this->assertEquals('Duplicate payment detected for INV-001', $rejected->rejectionReason);
    }

    #[Test]
    #[DataProvider('approvalThresholdProvider')]
    public function it_determines_required_approval_levels(
        float $amount,
        int $expectedLevels,
    ): void {
        $batchAmount = Money::of($amount, 'USD');

        $requiredLevels = $this->service->getRequiredApprovalLevels($batchAmount);

        $this->assertEquals($expectedLevels, $requiredLevels);
    }

    public static function approvalThresholdProvider(): array
    {
        return [
            'under 10K - Level 1' => [9999.99, 1],
            '10K to 50K - Level 2' => [25000.00, 2],
            '50K to 100K - Level 3' => [75000.00, 3],
            '100K to 500K - Level 4' => [300000.00, 4],
            'over 500K - Level 5' => [1000000.00, 5],
        ];
    }

    #[Test]
    public function it_checks_user_approval_authority(): void
    {
        $batchAmount = Money::of(75000.00, 'USD'); // Requires Level 3

        // User with Level 3+ can approve
        $canApprove = $this->service->canUserApprove(
            batchAmount: $batchAmount,
            userApprovalLevel: 3,
        );
        $this->assertTrue($canApprove);

        // User with Level 2 cannot approve
        $cannotApprove = $this->service->canUserApprove(
            batchAmount: $batchAmount,
            userApprovalLevel: 2,
        );
        $this->assertFalse($cannotApprove);
    }

    #[Test]
    public function it_generates_nacha_file_for_ach_batch(): void
    {
        $batch = $this->createBatchWithItems();
        $approved = $this->approveBatch($batch);

        $result = $this->service->generateNachaFile(
            batchId: $approved->batchId,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
        );

        $this->assertTrue($result->validationPassed);
        $this->assertEquals('NACHA', $result->format);
        $this->assertNotEmpty($result->fileName);
        $this->assertNotEmpty($result->fileContent);
        $this->assertStringEndsWith('.ach', $result->fileName);

        // Verify NACHA structure
        $lines = explode("\n", $result->fileContent);
        $this->assertStringStartsWith('1', $lines[0]); // File Header
    }

    #[Test]
    public function it_generates_iso20022_file_for_wire_batch(): void
    {
        // Create WIRE batch
        $batch = $this->service->createBatch(
            batchName: 'International Wire',
            paymentMethod: 'WIRE',
            paymentDate: new \DateTimeImmutable('+5 days'),
            currency: 'EUR',
            createdBy: 'USER-001',
        );

        $batch = $this->service->addPaymentItem(
            batchId: $batch->batchId,
            invoiceId: 'INV-INTL-001',
            vendorId: 'VENDOR-EU-001',
            vendorName: 'European Supplier GmbH',
            paymentAmount: Money::of(25000.00, 'EUR'),
            invoiceNumber: 'EU-2024-001',
            beneficiaryAccountNumber: 'DE89370400440532013000',
            beneficiaryBankSwift: 'COBADEFFXXX',
        );

        $approved = $this->approveBatch($batch);

        $result = $this->service->generateIso20022File(
            batchId: $approved->batchId,
            initiatingPartyName: 'Test Company Inc',
            initiatingPartyId: 'TESTCOMPID',
            debtorAccountIban: 'US12345678901234567890',
            debtorBankBic: 'CHASUS33XXX',
        );

        $this->assertTrue($result->validationPassed);
        $this->assertEquals('ISO20022', $result->format);
        $this->assertStringEndsWith('.xml', $result->fileName);
        $this->assertStringContainsString('<CstmrCdtTrfInitn>', $result->fileContent);
        $this->assertStringContainsString('<MsgId>', $result->fileContent);
    }

    #[Test]
    public function it_fails_bank_file_generation_for_unapproved_batch(): void
    {
        $batch = $this->createBatchWithItems();
        // Don't approve - still in DRAFT status

        $result = $this->service->generateNachaFile(
            batchId: $batch->batchId,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
        );

        $this->assertFalse($result->validationPassed);
        $this->assertNotEmpty($result->validationErrors);
    }

    #[Test]
    public function it_processes_completed_batch(): void
    {
        $this->eventDispatcher
            ->expects($this->exactly(4)) // Create + Submit + Approve + Process
            ->method('dispatch');

        $batch = $this->createBatchWithItems();
        $approved = $this->approveBatch($batch);

        // Generate bank file first
        $this->service->generateNachaFile(
            batchId: $approved->batchId,
            immediateOrigin: '0123456789',
            immediateDestination: '0987654321',
            companyName: 'Test Company',
            companyId: '1234567890',
        );

        // Mark as processed
        $processed = $this->service->processCompletedBatch(
            batchId: $approved->batchId,
            bankReference: 'BANK-REF-12345',
            processedBy: 'TREASURY-001',
        );

        $this->assertEquals(PaymentBatchStatus::PROCESSED, $processed->status);
    }

    #[Test]
    public function it_prevents_adding_items_to_non_draft_batch(): void
    {
        $batch = $this->createBatchWithItems();
        $submitted = $this->service->submitForApproval($batch->batchId, 'USER-001');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot add items to a batch that is not in DRAFT status');

        $this->service->addPaymentItem(
            batchId: $submitted->batchId,
            invoiceId: 'INV-NEW',
            vendorId: 'VENDOR-NEW',
            vendorName: 'New Vendor',
            paymentAmount: Money::of(1000.00, 'USD'),
            invoiceNumber: 'INV-NEW-001',
            routingNumber: '111111111',
            bankAccountNumber: '222222222',
        );
    }

    #[Test]
    public function it_calculates_batch_totals_correctly(): void
    {
        $batch = $this->service->createBatch(
            batchName: 'Multi-Item Batch',
            paymentMethod: 'ACH',
            paymentDate: new \DateTimeImmutable('+3 days'),
            currency: 'USD',
            createdBy: 'USER-001',
        );

        // Add multiple items
        $batch = $this->service->addPaymentItem(
            batchId: $batch->batchId,
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Vendor One',
            paymentAmount: Money::of(5000.00, 'USD'),
            invoiceNumber: 'INV-001',
            routingNumber: '111111111',
            bankAccountNumber: '111111111',
        );

        $batch = $this->service->addPaymentItem(
            batchId: $batch->batchId,
            invoiceId: 'INV-002',
            vendorId: 'VENDOR-002',
            vendorName: 'Vendor Two',
            paymentAmount: Money::of(3500.00, 'USD'),
            invoiceNumber: 'INV-002',
            routingNumber: '222222222',
            bankAccountNumber: '222222222',
        );

        $batch = $this->service->addPaymentItem(
            batchId: $batch->batchId,
            invoiceId: 'INV-003',
            vendorId: 'VENDOR-003',
            vendorName: 'Vendor Three',
            paymentAmount: Money::of(1500.00, 'USD'),
            invoiceNumber: 'INV-003',
            routingNumber: '333333333',
            bankAccountNumber: '333333333',
        );

        $this->assertEquals(3, $batch->itemCount);
        $this->assertEquals(10000.00, $batch->totalAmount->getAmount());
    }

    /**
     * Helper: Create a batch with items for testing.
     */
    private function createBatchWithItems(): PaymentBatchData
    {
        $batch = $this->service->createBatch(
            batchName: 'Test Batch',
            paymentMethod: 'ACH',
            paymentDate: new \DateTimeImmutable('+3 days'),
            currency: 'USD',
            createdBy: 'USER-001',
        );

        return $this->service->addPaymentItem(
            batchId: $batch->batchId,
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor Inc',
            paymentAmount: Money::of(5000.00, 'USD'),
            invoiceNumber: 'INV-2024-001',
            routingNumber: '123456789',
            bankAccountNumber: '9876543210',
        );
    }

    /**
     * Helper: Submit and approve a batch.
     */
    private function approveBatch(PaymentBatchData $batch): PaymentBatchData
    {
        $submitted = $this->service->submitForApproval($batch->batchId, 'USER-001');

        return $this->service->approve(
            batchId: $submitted->batchId,
            approverId: 'APPROVER-001',
            approverLevel: 5, // High authority
        );
    }
}
