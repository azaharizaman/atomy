<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\TaxValidationServiceInterface;
use Nexus\ProcurementOperations\Coordinators\WithholdingCertificateGeneratorInterface;
use Nexus\ProcurementOperations\Coordinators\WithholdingTaxCoordinator;
use Nexus\ProcurementOperations\Coordinators\WithholdingTaxRemittanceInterface;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxCalculation;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxComponent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(WithholdingTaxCoordinator::class)]
final class WithholdingTaxCoordinatorTest extends TestCase
{
    private WithholdingTaxCoordinator $coordinator;
    private MockObject&TaxValidationServiceInterface $taxService;
    private MockObject&WithholdingTaxRemittanceInterface $remittanceService;
    private MockObject&WithholdingCertificateGeneratorInterface $certificateGenerator;
    private MockObject&EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->taxService = $this->createMock(TaxValidationServiceInterface::class);
        $this->remittanceService = $this->createMock(WithholdingTaxRemittanceInterface::class);
        $this->certificateGenerator = $this->createMock(WithholdingCertificateGeneratorInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->coordinator = new WithholdingTaxCoordinator(
            taxService: $this->taxService,
            remittanceService: $this->remittanceService,
            certificateGenerator: $this->certificateGenerator,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function processWithholding_calculates_and_records_withholding(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';
        $invoiceId = 'inv-001';
        $amount = Money::of(10000, 'MYR');

        $calculation = WithholdingTaxCalculation::withWithholding(
            grossAmount: $amount,
            withholdingAmount: Money::of(1500, 'MYR'),
            netPayable: Money::of(8500, 'MYR'),
            rate: 15.0,
            components: [
                WithholdingTaxComponent::serviceFee(
                    rate: 15.0,
                    amount: Money::of(1500, 'MYR'),
                    authority: 'LHDN',
                ),
            ],
        );

        $this->taxService
            ->method('calculateWithholdingTax')
            ->willReturn($calculation);

        $this->remittanceService
            ->expects($this->once())
            ->method('recordWithholding')
            ->with(
                $tenantId,
                $vendorId,
                $invoiceId,
                $this->anything(),
            );

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof \Nexus\ProcurementOperations\Events\Withholding\WithholdingTaxCalculatedEvent;
            }));

        $result = $this->coordinator->processWithholding(
            tenantId: $tenantId,
            vendorId: $vendorId,
            invoiceId: $invoiceId,
            grossAmount: $amount,
            paymentType: 'service_fee',
        );

        $this->assertTrue($result->hasWithholding);
        $this->assertEquals(150000, $result->withholdingAmount->getAmountInCents());
        $this->assertEquals(850000, $result->netPayable->getAmountInCents());
    }

    #[Test]
    public function processWithholding_handles_no_withholding(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';
        $invoiceId = 'inv-001';
        $amount = Money::of(10000, 'MYR');

        $calculation = WithholdingTaxCalculation::noWithholding(
            grossAmount: $amount,
            reason: 'Domestic vendor not subject to withholding',
        );

        $this->taxService
            ->method('calculateWithholdingTax')
            ->willReturn($calculation);

        // Should not record anything
        $this->remittanceService
            ->expects($this->never())
            ->method('recordWithholding');

        $result = $this->coordinator->processWithholding(
            tenantId: $tenantId,
            vendorId: $vendorId,
            invoiceId: $invoiceId,
            grossAmount: $amount,
            paymentType: 'goods',
        );

        $this->assertFalse($result->hasWithholding);
        $this->assertEquals(1000000, $result->netPayable->getAmountInCents());
    }

    #[Test]
    public function processBatchWithholding_processes_multiple_invoices(): void
    {
        $tenantId = 'tenant-123';

        $invoices = [
            [
                'vendor_id' => 'vendor-001',
                'invoice_id' => 'inv-001',
                'amount' => Money::of(10000, 'MYR'),
                'payment_type' => 'service_fee',
            ],
            [
                'vendor_id' => 'vendor-002',
                'invoice_id' => 'inv-002',
                'amount' => Money::of(5000, 'MYR'),
                'payment_type' => 'royalty',
            ],
        ];

        $this->taxService
            ->method('calculateWithholdingTax')
            ->willReturnOnConsecutiveCalls(
                WithholdingTaxCalculation::withWithholding(
                    grossAmount: Money::of(10000, 'MYR'),
                    withholdingAmount: Money::of(1500, 'MYR'),
                    netPayable: Money::of(8500, 'MYR'),
                    rate: 15.0,
                    components: [],
                ),
                WithholdingTaxCalculation::withWithholding(
                    grossAmount: Money::of(5000, 'MYR'),
                    withholdingAmount: Money::of(500, 'MYR'),
                    netPayable: Money::of(4500, 'MYR'),
                    rate: 10.0,
                    components: [],
                ),
            );

        $results = $this->coordinator->processBatchWithholding($tenantId, $invoices);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->hasWithholding);
        $this->assertTrue($results[1]->hasWithholding);
    }

    #[Test]
    public function getPeriodSummary_returns_period_statistics(): void
    {
        $tenantId = 'tenant-123';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->remittanceService
            ->method('getPeriodSummary')
            ->with($tenantId, $startDate, $endDate)
            ->willReturn([
                'total_withheld' => 50000_00, // cents
                'total_remitted' => 35000_00,
                'pending_remittance' => 15000_00,
                'invoice_count' => 25,
                'vendor_count' => 10,
                'by_type' => [
                    'service_fee' => 30000_00,
                    'royalty' => 15000_00,
                    'interest' => 5000_00,
                ],
            ]);

        $summary = $this->coordinator->getPeriodSummary($tenantId, $startDate, $endDate);

        $this->assertEquals(500_00_00, $summary->totalWithheld->getAmountInCents());
        $this->assertEquals(350_00_00, $summary->totalRemitted->getAmountInCents());
        $this->assertEquals(25, $summary->invoiceCount);
        $this->assertEquals(10, $summary->vendorCount);
    }

    #[Test]
    public function getPendingRemittances_returns_due_remittances(): void
    {
        $tenantId = 'tenant-123';

        $this->remittanceService
            ->method('getPendingRemittances')
            ->with($tenantId)
            ->willReturn([
                [
                    'remittance_id' => 'rem-001',
                    'due_date' => '2024-02-15',
                    'amount' => 15000_00,
                    'currency' => 'MYR',
                    'vendor_count' => 5,
                    'authority' => 'LHDN',
                ],
                [
                    'remittance_id' => 'rem-002',
                    'due_date' => '2024-02-28',
                    'amount' => 8000_00,
                    'currency' => 'MYR',
                    'vendor_count' => 3,
                    'authority' => 'LHDN',
                ],
            ]);

        $pending = $this->coordinator->getPendingRemittances($tenantId);

        $this->assertCount(2, $pending);
        $this->assertEquals('rem-001', $pending[0]->remittanceId);
        $this->assertEquals(5, $pending[0]->vendorCount);
    }

    #[Test]
    public function markRemittancePaid_updates_status_and_generates_certificates(): void
    {
        $tenantId = 'tenant-123';
        $remittanceId = 'rem-001';
        $paymentReference = 'PAY-20240215-001';
        $paymentDate = new \DateTimeImmutable('2024-02-15');

        $this->remittanceService
            ->method('getRemittanceDetails')
            ->with($tenantId, $remittanceId)
            ->willReturn([
                'vendors' => ['vendor-001', 'vendor-002'],
                'invoices' => ['inv-001', 'inv-002', 'inv-003'],
            ]);

        $this->remittanceService
            ->expects($this->once())
            ->method('markPaid')
            ->with($tenantId, $remittanceId, $paymentReference, $paymentDate);

        $this->certificateGenerator
            ->expects($this->exactly(2))
            ->method('generateCertificate')
            ->willReturn('cert-content');

        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch');

        $this->coordinator->markRemittancePaid(
            tenantId: $tenantId,
            remittanceId: $remittanceId,
            paymentReference: $paymentReference,
            paymentDate: $paymentDate,
        );
    }

    #[Test]
    public function generateCertificate_creates_vendor_certificate(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';
        $year = 2024;

        $this->certificateGenerator
            ->expects($this->once())
            ->method('generateAnnualCertificate')
            ->with($tenantId, $vendorId, $year)
            ->willReturn('certificate-pdf-content');

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof \Nexus\ProcurementOperations\Events\Withholding\WithholdingCertificateGeneratedEvent;
            }));

        $certificate = $this->coordinator->generateCertificate(
            tenantId: $tenantId,
            vendorId: $vendorId,
            year: $year,
        );

        $this->assertNotEmpty($certificate);
    }
}
