<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Enums\PaymentBatchStatus;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchCreatedEvent;
use Nexus\ProcurementOperations\Services\PaymentProcessingService;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
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
            tenantId: 'tenant-1',
            paymentMethod: 'ACH',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable('+3 days'),
            currency: 'USD',
            createdBy: 'USER-001'
        );

        $this->assertInstanceOf(PaymentBatchData::class, $batch);
        $this->assertNotEmpty($batch->batchId);
    }

    #[Test]
    public function it_creates_payment_batch_with_custom_id_generator(): void
    {
        $idGenerator = $this->createMock(SecureIdGeneratorInterface::class);
        $idGenerator
            ->expects($this->once())
            ->method('generateId')
            ->with('batch-', 12)
            ->willReturn('custom-batch-id-123');

        $service = new PaymentProcessingService(
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
            idGenerator: $idGenerator,
        );

        $batch = $service->createBatch(
            tenantId: 'tenant-1',
            paymentMethod: 'ACH',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable('+3 days'),
            currency: 'USD',
            createdBy: 'USER-001'
        );

        $this->assertSame('custom-batch-id-123', $batch->batchId);
    }

    #[Test]
    public function it_adds_payment_item_to_batch_ach(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'ACH',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $item = $this->service->addPaymentItem(
            batch: $batch,
            vendorId: 'V1',
            vendorName: 'Test Vendor',
            amount: Money::of(100, 'USD'),
            invoiceIds: ['inv-1'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021'
        );

        $this->assertInstanceOf(PaymentItemData::class, $item);
        $this->assertNotEmpty($item->paymentItemId);
    }

    #[Test]
    public function it_adds_payment_item_to_batch_wire(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'WIRE',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $item = $this->service->addPaymentItem(
            batch: $batch,
            vendorId: 'V1',
            vendorName: 'Test Vendor',
            amount: Money::of(100, 'USD'),
            invoiceIds: ['inv-1'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
            vendorBankSwiftCode: 'SWIFT123'
        );

        $this->assertInstanceOf(PaymentItemData::class, $item);
    }

    #[Test]
    public function it_adds_payment_item_to_batch_check(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'CHECK',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $item = $this->service->addPaymentItem(
            batch: $batch,
            vendorId: 'V1',
            vendorName: 'Test Vendor',
            amount: Money::of(100, 'USD'),
            invoiceIds: ['inv-1'],
            vendorBankAccount: '123456789',
            vendorBankRoutingNumber: '021000021',
            vendorBankSwiftCode: null,
            checkPayeeName: 'Payee Name',
            checkMailingAddress: '123 Main St'
        );

        $this->assertInstanceOf(PaymentItemData::class, $item);
    }

    #[Test]
    public function it_throws_for_unsupported_payment_method(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'UNKNOWN',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment method: UNKNOWN');

        $this->service->addPaymentItem(
            batch: $batch,
            vendorId: 'V1',
            vendorName: 'Test Vendor',
            amount: Money::of(100, 'USD'),
            invoiceIds: ['inv-1'],
            vendorBankAccount: '123',
            vendorBankRoutingNumber: '456'
        );
    }

    #[Test]
    public function it_adds_payment_item_with_multiple_invoices(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'ACH',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $item = $this->service->addPaymentItem(
            batch: $batch,
            vendorId: 'V1',
            vendorName: 'Test Vendor',
            amount: Money::of(100, 'USD'),
            invoiceIds: ['inv-1', 'inv-2', 'inv-3'],
            vendorBankAccount: '123',
            vendorBankRoutingNumber: '456'
        );

        $this->assertInstanceOf(PaymentItemData::class, $item);
    }

    #[Test]
    public function it_validates_batch_and_item(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'ACH',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );
        
        $item = PaymentItemData::forAch(
            paymentItemId: 'item-1',
            vendorId: 'V1',
            vendorName: 'V1',
            amount: Money::of(100, 'USD'),
            invoiceIds: ['inv-1'],
            paymentReference: 'INV1',
            bankAccountNumber: '123',
            routingNumber: '456',
            bankName: 'Test Bank',
            accountName: 'Test Account'
        );

        $this->assertSame([], $this->service->validateBatch($batch));
        $this->assertSame([], $this->service->validatePaymentItem($item));
    }

    #[Test]
    public function it_generates_bank_files(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'ACH',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $this->assertNotEmpty($this->service->generateBankFile($batch)->fileContent);
        $this->assertNotEmpty($this->service->generateNachaFile($batch)->fileContent);
        $this->assertNotEmpty($this->service->generateIso20022File($batch)->fileContent);
        $this->assertNotEmpty($this->service->generateCheckPrintFile($batch)->fileContent);
    }

    #[Test]
    public function it_generates_nacha_file_with_correct_format(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'test-batch-123',
            batchNumber: 'BN-123',
            tenantId: 'tenant-1',
            paymentMethod: 'ACH',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $result = $this->service->generateNachaFile($batch);

        $this->assertStringContainsString('ACH_test-batch-123.ach', $result->fileName);
        $this->assertStringContainsString('MOCK NACHA CONTENT', $result->fileContent);
    }

    #[Test]
    public function it_generates_iso20022_file_with_correct_format(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'test-batch-456',
            batchNumber: 'BN-456',
            tenantId: 'tenant-1',
            paymentMethod: 'WIRE',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $result = $this->service->generateIso20022File($batch);

        $this->assertStringContainsString('pain.001_test-batch-456.xml', $result->fileName);
        $this->assertStringContainsString('MOCK ISO20022 CONTENT', $result->fileContent);
        $this->assertArrayHasKey('message_type', $result->metadata);
        $this->assertSame('pain.001.001.03', $result->metadata['message_type']);
    }

    #[Test]
    public function it_generates_check_print_file(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'test-batch-789',
            batchNumber: 'BN-789',
            tenantId: 'tenant-1',
            paymentMethod: 'CHECK',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $result = $this->service->generateCheckPrintFile($batch);

        $this->assertStringContainsString('checks_test-batch-789.pdf', $result->fileName);
        $this->assertStringContainsString('MOCK CHECK CONTENT', $result->fileContent);
    }

    #[Test]
    public function it_returns_default_settings(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'ACH',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $this->assertEquals(1, $this->service->getRequiredApprovalLevels($batch));
        $this->assertTrue($this->service->canUserApprove('u1', $batch, 1));
        $this->assertEquals(0, $this->service->estimateBankFees($batch)->getAmount());
        $this->assertTrue($this->service->validateVendorBankingDetails('v1', 'ACH')['valid']);
    }

    #[Test]
    public function it_returns_empty_query_results(): void
    {
        $this->assertSame([], $this->service->getVendorPaymentHistory('t1', 'v1'));
        $this->assertSame([], $this->service->getPendingBatchesForApproval('t1', 'u1'));
    }

    #[Test]
    public function it_returns_vendor_payment_history_with_dates(): void
    {
        $fromDate = new \DateTimeImmutable('2024-01-01');
        $toDate = new \DateTimeImmutable('2024-12-31');

        $result = $this->service->getVendorPaymentHistory('t1', 'v1', $fromDate, $toDate);

        $this->assertIsArray($result);
        $this->assertSame([], $result);
    }

    #[Test]
    public function it_validates_vendor_banking_details_returns_errors(): void
    {
        $result = $this->service->validateVendorBankingDetails('v1', 'WIRE');

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertTrue($result['valid']);
        $this->assertIsArray($result['errors']);
    }

    #[Test]
    public function it_creates_batch_with_different_currencies(): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(PaymentBatchCreatedEvent::class));

        $batch = $this->service->createBatch(
            tenantId: 'tenant-1',
            paymentMethod: 'WIRE',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable('+5 days'),
            currency: 'EUR',
            createdBy: 'USER-001'
        );

        $this->assertInstanceOf(PaymentBatchData::class, $batch);
        $this->assertSame('EUR', $batch->currency);
    }

    #[Test]
    public function it_can_user_approve_different_levels(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'ACH',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $this->assertTrue($this->service->canUserApprove('user-1', $batch, 1));
        $this->assertTrue($this->service->canUserApprove('user-2', $batch, 2));
        $this->assertTrue($this->service->canUserApprove('admin', $batch, 3));
    }

    #[Test]
    public function it_estimates_bank_fees_returns_money_object(): void
    {
        $batch = PaymentBatchData::create(
            batchId: 'batch-1',
            batchNumber: 'BN-1',
            tenantId: 'tenant-1',
            paymentMethod: 'WIRE',
            bankAccountId: 'BANK-001',
            paymentDate: new \DateTimeImmutable(),
            currency: 'USD',
            createdBy: 'user-1'
        );

        $fees = $this->service->estimateBankFees($batch);

        $this->assertInstanceOf(Money::class, $fees);
    }
}
