<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\MultiEntityPaymentServiceInterface;
use Nexus\ProcurementOperations\Coordinators\MultiEntityPaymentCoordinator;
use Nexus\ProcurementOperations\DTOs\Financial\MultiEntityPaymentBatch;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Events\Financial\MultiEntityPaymentBatchCreatedEvent;
use Nexus\ProcurementOperations\Events\Financial\MultiEntityPaymentBatchExecutedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(MultiEntityPaymentCoordinator::class)]
final class MultiEntityPaymentCoordinatorTest extends TestCase
{
    private MultiEntityPaymentServiceInterface&MockObject $paymentService;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private MultiEntityPaymentCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->paymentService = $this->createMock(MultiEntityPaymentServiceInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->coordinator = new MultiEntityPaymentCoordinator(
            paymentService: $this->paymentService,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function createPaymentBatchCreatesValidBatchAndDispatchesEvent(): void
    {
        // Arrange
        $tenantId = 'tenant-1';
        $entityId = 'entity-us';
        $paymentMethod = 'ACH';
        $createdBy = 'user-ap';

        $paymentItem = new PaymentItemData(
            paymentItemId: 'PI-001',
            vendorId: 'vendor-acme',
            vendorName: 'Acme Corp',
            invoiceId: 'INV-001',
            invoiceNumber: 'ACME-2024-001',
            amount: Money::of(15000.00, 'USD'),
            discountAmount: Money::of(0, 'USD'),
            paymentDate: new \DateTimeImmutable('+1 day'),
            bankAccountNumber: '****1234',
            routingNumber: '121000248',
            metadata: [],
        );

        $this->paymentService
            ->expects($this->once())
            ->method('validatePaymentPermission')
            ->with($entityId, 'vendor-acme')
            ->willReturn(['valid' => true, 'reason' => null]);

        $this->paymentService
            ->expects($this->once())
            ->method('selectOptimalBank')
            ->with($entityId, $this->isInstanceOf(Money::class), $paymentMethod)
            ->willReturn([
                'bank_id' => 'bank-01',
                'account_number' => '****5678',
                'available_balance' => Money::of(100000.00, 'USD'),
            ]);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(MultiEntityPaymentBatchCreatedEvent::class))
            ->willReturnArgument(0);

        // Act
        $result = $this->coordinator->createPaymentBatch(
            tenantId: $tenantId,
            entityId: $entityId,
            paymentItems: [$paymentItem],
            paymentMethod: $paymentMethod,
            createdBy: $createdBy,
        );

        // Assert
        $this->assertArrayHasKey('batch', $result);
        $this->assertInstanceOf(MultiEntityPaymentBatch::class, $result['batch']);
        $this->assertSame($entityId, $result['batch']->entityId);
        $this->assertSame('bank-01', $result['batch']->bankId);
        $this->assertSame($paymentMethod, $result['batch']->paymentMethod);
        $this->assertSame(1, $result['batch']->getPaymentCount());
        $this->assertEquals(Money::of(15000.00, 'USD'), $result['batch']->totalAmount);
        $this->assertEmpty($result['warnings']);
    }

    #[Test]
    public function createPaymentBatchFiltersInvalidPaymentsAndAddsWarnings(): void
    {
        // Arrange
        $validPayment = new PaymentItemData(
            paymentItemId: 'PI-001',
            vendorId: 'vendor-valid',
            vendorName: 'Valid Corp',
            invoiceId: 'INV-001',
            invoiceNumber: 'VALID-001',
            amount: Money::of(10000.00, 'USD'),
            discountAmount: Money::of(0, 'USD'),
            paymentDate: new \DateTimeImmutable('+1 day'),
            bankAccountNumber: '****1234',
            routingNumber: '121000248',
            metadata: [],
        );

        $invalidPayment = new PaymentItemData(
            paymentItemId: 'PI-002',
            vendorId: 'vendor-blocked',
            vendorName: 'Blocked Corp',
            invoiceId: 'INV-002',
            invoiceNumber: 'BLOCKED-001',
            amount: Money::of(5000.00, 'USD'),
            discountAmount: Money::of(0, 'USD'),
            paymentDate: new \DateTimeImmutable('+1 day'),
            bankAccountNumber: '****5678',
            routingNumber: '121000248',
            metadata: [],
        );

        $this->paymentService
            ->expects($this->exactly(2))
            ->method('validatePaymentPermission')
            ->willReturnCallback(function ($entityId, $vendorId) {
                if ($vendorId === 'vendor-blocked') {
                    return ['valid' => false, 'reason' => 'Vendor on hold'];
                }
                return ['valid' => true, 'reason' => null];
            });

        $this->paymentService
            ->expects($this->once())
            ->method('selectOptimalBank')
            ->willReturn([
                'bank_id' => 'bank-01',
                'account_number' => '****5678',
            ]);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        // Act
        $result = $this->coordinator->createPaymentBatch(
            tenantId: 'tenant-1',
            entityId: 'entity-us',
            paymentItems: [$validPayment, $invalidPayment],
            paymentMethod: 'ACH',
            createdBy: 'user-ap',
        );

        // Assert
        $this->assertSame(1, $result['batch']->getPaymentCount());
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('PI-002', $result['warnings'][0]);
        $this->assertStringContainsString('Vendor on hold', $result['warnings'][0]);
    }

    #[Test]
    public function createPaymentBatchThrowsWhenNoValidPayments(): void
    {
        // Arrange
        $payment = new PaymentItemData(
            paymentItemId: 'PI-001',
            vendorId: 'vendor-blocked',
            vendorName: 'Blocked Corp',
            invoiceId: 'INV-001',
            invoiceNumber: 'BLOCKED-001',
            amount: Money::of(5000.00, 'USD'),
            discountAmount: Money::of(0, 'USD'),
            paymentDate: new \DateTimeImmutable('+1 day'),
            bankAccountNumber: '****1234',
            routingNumber: '121000248',
            metadata: [],
        );

        $this->paymentService
            ->expects($this->once())
            ->method('validatePaymentPermission')
            ->willReturn(['valid' => false, 'reason' => 'Vendor on hold']);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No valid payment items');

        $this->coordinator->createPaymentBatch(
            tenantId: 'tenant-1',
            entityId: 'entity-us',
            paymentItems: [$payment],
            paymentMethod: 'ACH',
            createdBy: 'user-ap',
        );
    }

    #[Test]
    public function getApprovalChainReturnsAuthorizationLevels(): void
    {
        // Arrange
        $batch = new MultiEntityPaymentBatch(
            batchId: 'MEPB-001',
            entityId: 'entity-us',
            entityName: 'US Entity',
            bankId: 'bank-01',
            bankAccountNumber: '****1234',
            totalAmount: Money::of(50000.00, 'USD'),
            currency: 'USD',
            paymentMethod: 'ACH',
            paymentItems: [],
            executionDate: new \DateTimeImmutable('+1 day'),
            metadata: [],
        );

        $authChain = [
            ['level' => 1, 'role' => 'AP_Manager', 'threshold' => 25000],
            ['level' => 2, 'role' => 'Controller', 'threshold' => 50000],
            ['level' => 3, 'role' => 'CFO', 'threshold' => null],
        ];

        $this->paymentService
            ->expects($this->once())
            ->method('getAuthorizationChain')
            ->with($batch->entityId, $batch->totalAmount)
            ->willReturn($authChain);

        // Act
        $result = $this->coordinator->getApprovalChain($batch);

        // Assert
        $this->assertSame('MEPB-001', $result['batch_id']);
        $this->assertCount(3, $result['levels']);
        $this->assertSame(3, $result['required_approvals']);
        $this->assertSame(0, $result['current_level']);
    }

    #[Test]
    public function executeBatchProcessesApprovedBatchAndDispatchesEvent(): void
    {
        // Arrange
        $batch = new MultiEntityPaymentBatch(
            batchId: 'MEPB-001',
            entityId: 'entity-us',
            entityName: 'US Entity',
            bankId: 'bank-01',
            bankAccountNumber: '****1234',
            totalAmount: Money::of(50000.00, 'USD'),
            currency: 'USD',
            paymentMethod: 'ACH',
            paymentItems: [],
            executionDate: new \DateTimeImmutable('+1 day'),
            metadata: [],
            approvedBy: 'user-cfo',
            approvedAt: new \DateTimeImmutable(),
        );

        $executionResult = new class {
            public int $successfulPayments = 10;
            public int $failedPayments = 0;
            public Money $totalPaid;
            public Money $totalFailed;
            public array $details = [];

            public function __construct()
            {
                $this->totalPaid = Money::of(50000.00, 'USD');
                $this->totalFailed = Money::of(0, 'USD');
            }
        };

        $this->paymentService
            ->expects($this->once())
            ->method('executePaymentBatch')
            ->with($batch)
            ->willReturn($executionResult);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(MultiEntityPaymentBatchExecutedEvent::class))
            ->willReturnArgument(0);

        // Act
        $result = $this->coordinator->executeBatch(
            tenantId: 'tenant-1',
            batch: $batch,
            executedBy: 'user-treasury',
        );

        // Assert
        $this->assertSame('MEPB-001', $result['batch_id']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame(10, $result['successful_payments']);
        $this->assertSame(0, $result['failed_payments']);
        $this->assertEquals(Money::of(50000.00, 'USD'), $result['total_paid']);
    }

    #[Test]
    public function executeBatchThrowsWhenBatchNotApproved(): void
    {
        // Arrange
        $batch = new MultiEntityPaymentBatch(
            batchId: 'MEPB-001',
            entityId: 'entity-us',
            entityName: 'US Entity',
            bankId: 'bank-01',
            bankAccountNumber: '****1234',
            totalAmount: Money::of(50000.00, 'USD'),
            currency: 'USD',
            paymentMethod: 'ACH',
            paymentItems: [],
            executionDate: new \DateTimeImmutable('+1 day'),
            metadata: [],
            // Not approved - no approvedBy/approvedAt
        );

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not approved');

        $this->coordinator->executeBatch(
            tenantId: 'tenant-1',
            batch: $batch,
            executedBy: 'user-treasury',
        );
    }

    #[Test]
    public function createConsolidatedBatchesCreatesMultipleBatches(): void
    {
        // Arrange
        $usPayment = new PaymentItemData(
            paymentItemId: 'PI-US-001',
            vendorId: 'vendor-us',
            vendorName: 'US Vendor',
            invoiceId: 'INV-US-001',
            invoiceNumber: 'US-001',
            amount: Money::of(20000.00, 'USD'),
            discountAmount: Money::of(0, 'USD'),
            paymentDate: new \DateTimeImmutable('+1 day'),
            bankAccountNumber: '****1234',
            routingNumber: '121000248',
            metadata: [],
        );

        $ukPayment = new PaymentItemData(
            paymentItemId: 'PI-UK-001',
            vendorId: 'vendor-uk',
            vendorName: 'UK Vendor',
            invoiceId: 'INV-UK-001',
            invoiceNumber: 'UK-001',
            amount: Money::of(15000.00, 'USD'),
            discountAmount: Money::of(0, 'USD'),
            paymentDate: new \DateTimeImmutable('+1 day'),
            bankAccountNumber: '****5678',
            routingNumber: '026009593',
            metadata: [],
        );

        $entityPayments = [
            'entity-us' => [$usPayment],
            'entity-uk' => [$ukPayment],
        ];

        $this->paymentService
            ->method('validatePaymentPermission')
            ->willReturn(['valid' => true, 'reason' => null]);

        $this->paymentService
            ->method('selectOptimalBank')
            ->willReturn([
                'bank_id' => 'bank-01',
                'account_number' => '****9999',
            ]);

        $this->eventDispatcher
            ->expects($this->exactly(2))
            ->method('dispatch');

        // Act
        $result = $this->coordinator->createConsolidatedBatches(
            tenantId: 'tenant-1',
            entityPayments: $entityPayments,
            paymentMethod: 'WIRE',
            createdBy: 'user-treasury',
        );

        // Assert
        $this->assertCount(2, $result['batches']);
        $this->assertSame(2, $result['summary']['entity_count']);
        $this->assertSame(2, $result['summary']['total_payments']);
        $this->assertSame('WIRE', $result['summary']['payment_method']);
    }

    #[Test]
    public function getCrossEntityStatsConsolidatesStatisticsAcrossEntities(): void
    {
        // Arrange
        $entityIds = ['entity-us', 'entity-uk'];
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');

        $this->paymentService
            ->expects($this->exactly(2))
            ->method('getEntityPaymentStats')
            ->willReturnCallback(function ($entityId) {
                if ($entityId === 'entity-us') {
                    return [
                        'total_paid' => Money::of(100000.00, 'USD'),
                        'payment_count' => 50,
                        'by_method' => [
                            'ACH' => ['count' => 40, 'amount' => Money::of(80000.00, 'USD')],
                            'WIRE' => ['count' => 10, 'amount' => Money::of(20000.00, 'USD')],
                        ],
                    ];
                }
                return [
                    'total_paid' => Money::of(75000.00, 'USD'),
                    'payment_count' => 30,
                    'by_method' => [
                        'ACH' => ['count' => 20, 'amount' => Money::of(50000.00, 'USD')],
                        'WIRE' => ['count' => 10, 'amount' => Money::of(25000.00, 'USD')],
                    ],
                ];
            });

        // Act
        $result = $this->coordinator->getCrossEntityStats(
            tenantId: 'tenant-1',
            entityIds: $entityIds,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );

        // Assert
        $this->assertSame(2, $result['consolidated']['entity_count']);
        $this->assertSame(80, $result['consolidated']['payment_count']);
        $this->assertEquals(Money::of(175000.00, 'USD'), $result['consolidated']['total_paid']);
        $this->assertSame(60, $result['consolidated']['by_method']['ACH']['count']);
        $this->assertSame(20, $result['consolidated']['by_method']['WIRE']['count']);
        $this->assertArrayHasKey('entity-us', $result['by_entity']);
        $this->assertArrayHasKey('entity-uk', $result['by_entity']);
    }

    #[Test]
    public function validateVendorPaymentDelegatesToService(): void
    {
        // Arrange
        $this->paymentService
            ->expects($this->once())
            ->method('validatePaymentPermission')
            ->with('entity-us', 'vendor-acme')
            ->willReturn(['valid' => true, 'reason' => null]);

        // Act
        $result = $this->coordinator->validateVendorPayment('entity-us', 'vendor-acme');

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertNull($result['reason']);
    }

    #[Test]
    public function getAvailableBanksDelegatesToService(): void
    {
        // Arrange
        $banks = [
            ['bank_id' => 'bank-01', 'name' => 'Chase', 'balance' => Money::of(500000.00, 'USD')],
            ['bank_id' => 'bank-02', 'name' => 'BofA', 'balance' => Money::of(300000.00, 'USD')],
        ];

        $this->paymentService
            ->expects($this->once())
            ->method('getEntityPaymentBanks')
            ->with('entity-us', 'USD')
            ->willReturn($banks);

        // Act
        $result = $this->coordinator->getAvailableBanks('entity-us', 'USD');

        // Assert
        $this->assertCount(2, $result);
        $this->assertSame('Chase', $result[0]['name']);
        $this->assertSame('BofA', $result[1]['name']);
    }

    #[Test]
    public function createPaymentBatchRejectsElectronicPaymentWithIncompleteBankDetails(): void
    {
        // Arrange
        $payment = new PaymentItemData(
            paymentItemId: 'PI-001',
            vendorId: 'vendor-acme',
            vendorName: 'Acme Corp',
            invoiceId: 'INV-001',
            invoiceNumber: 'ACME-001',
            amount: Money::of(10000.00, 'USD'),
            discountAmount: Money::of(0, 'USD'),
            paymentDate: new \DateTimeImmutable('+1 day'),
            bankAccountNumber: null, // Missing bank details
            routingNumber: null,
            metadata: [],
        );

        $this->paymentService
            ->expects($this->once())
            ->method('validatePaymentPermission')
            ->willReturn(['valid' => true, 'reason' => null]);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No valid payment items');

        $this->coordinator->createPaymentBatch(
            tenantId: 'tenant-1',
            entityId: 'entity-us',
            paymentItems: [$payment],
            paymentMethod: 'ACH', // Electronic payment requires bank details
            createdBy: 'user-ap',
        );
    }

    #[Test]
    public function executeBatchReturnsPartialStatusOnPartialFailure(): void
    {
        // Arrange
        $batch = new MultiEntityPaymentBatch(
            batchId: 'MEPB-001',
            entityId: 'entity-us',
            entityName: 'US Entity',
            bankId: 'bank-01',
            bankAccountNumber: '****1234',
            totalAmount: Money::of(50000.00, 'USD'),
            currency: 'USD',
            paymentMethod: 'ACH',
            paymentItems: [],
            executionDate: new \DateTimeImmutable('+1 day'),
            metadata: [],
            approvedBy: 'user-cfo',
            approvedAt: new \DateTimeImmutable(),
        );

        $executionResult = new class {
            public int $successfulPayments = 8;
            public int $failedPayments = 2;
            public Money $totalPaid;
            public Money $totalFailed;
            public array $details = [
                ['payment_id' => 'PI-009', 'error' => 'Invalid account'],
                ['payment_id' => 'PI-010', 'error' => 'Insufficient funds'],
            ];

            public function __construct()
            {
                $this->totalPaid = Money::of(40000.00, 'USD');
                $this->totalFailed = Money::of(10000.00, 'USD');
            }
        };

        $this->paymentService
            ->expects($this->once())
            ->method('executePaymentBatch')
            ->willReturn($executionResult);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        // Act
        $result = $this->coordinator->executeBatch(
            tenantId: 'tenant-1',
            batch: $batch,
            executedBy: 'user-treasury',
        );

        // Assert
        $this->assertSame('partial', $result['status']);
        $this->assertSame(8, $result['successful_payments']);
        $this->assertSame(2, $result['failed_payments']);
        $this->assertCount(2, $result['details']);
    }
}
