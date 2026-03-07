<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\ProcurementOperations\Contracts\PaymentBatchBuilderInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\Coordinators\PaymentRunCoordinator;
use Nexus\ProcurementOperations\DTOs\PaymentBatchContext;
use Nexus\ProcurementOperations\DTOs\PaymentRunRequest;
use Nexus\ProcurementOperations\DTOs\ProcessPaymentRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(PaymentRunCoordinator::class)]
final class PaymentRunCoordinatorTest extends TestCase
{
    private PaymentBatchBuilderInterface&MockObject $batchBuilder;
    private SecureIdGeneratorInterface&MockObject $secureIdGenerator;
    private PaymentRunCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->batchBuilder = $this->createMock(PaymentBatchBuilderInterface::class);
        $this->secureIdGenerator = $this->createMock(SecureIdGeneratorInterface::class);

        $this->coordinator = new PaymentRunCoordinator(
            batchBuilder: $this->batchBuilder,
            logger: new NullLogger(),
            secureIdGenerator: $this->secureIdGenerator,
        );
    }

    #[Test]
    public function create_run_fails_when_vendor_bill_ids_are_missing(): void
    {
        $this->batchBuilder
            ->expects($this->never())
            ->method('buildBatch');

        $result = $this->coordinator->createRun(new PaymentRunRequest(
            tenantId: 'tenant-1',
            bankAccountId: 'bank-1',
            paymentDate: new \DateTimeImmutable('2026-01-01'),
            initiatedBy: 'user-1',
            filters: [],
            paymentMethod: 'ach',
        ));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('vendor bill ID', (string) $result->message);
    }

    #[Test]
    public function create_run_builds_batch_and_uses_secure_id_generator(): void
    {
        $this->batchBuilder
            ->expects($this->once())
            ->method('buildBatch')
            ->with($this->callback(function (ProcessPaymentRequest $request): bool {
                $this->assertSame('tenant-1', $request->tenantId);
                $this->assertSame(['bill-1', 'bill-2'], $request->vendorBillIds);
                $this->assertSame('ach', $request->paymentMethod);
                return true;
            }))
            ->willReturn($this->makeBatchContext());

        $this->secureIdGenerator
            ->expects($this->once())
            ->method('randomHex')
            ->with(8)
            ->willReturn('ABCDEF1234567890');

        $result = $this->coordinator->createRun(new PaymentRunRequest(
            tenantId: 'tenant-1',
            bankAccountId: 'bank-1',
            paymentDate: new \DateTimeImmutable('2026-01-01'),
            initiatedBy: 'user-1',
            filters: [
                'vendorBillIds' => ['bill-1', ' bill-2 ', ''],
                'takeEarlyPaymentDiscount' => true,
            ],
            paymentMethod: 'ach',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('prun-abcdef1234567890', $result->paymentRunId);
        $this->assertSame(2, $result->totalPayments);
        $this->assertSame(2250, $result->totalAmountCents);
        $this->assertSame('draft', $result->status);
    }

    #[Test]
    public function approve_run_requires_tenant_payment_run_and_actor(): void
    {
        $result = $this->coordinator->approveRun(
            tenantId: '',
            paymentRunId: 'prun-1',
            approvedBy: 'approver-1',
        );

        $this->assertFalse($result->success);
        $this->assertStringContainsString('required', (string) $result->message);
    }

    private function makeBatchContext(): PaymentBatchContext
    {
        $today = new \DateTimeImmutable('2026-01-01');

        return new PaymentBatchContext(
            tenantId: 'tenant-1',
            paymentBatchId: 'PAY-BATCH-20260101-ABC12345',
            paymentMethod: 'ach',
            bankAccountId: 'bank-1',
            totalAmountCents: 2250,
            totalDiscountCents: 250,
            netAmountCents: 2000,
            currency: 'USD',
            invoices: [
                [
                    'vendorBillId' => 'bill-1',
                    'vendorBillNumber' => 'VB-001',
                    'vendorId' => 'vendor-1',
                    'vendorName' => 'Vendor One',
                    'amountCents' => 1000,
                    'discountCents' => 0,
                    'netAmountCents' => 1000,
                    'dueDate' => $today,
                    'discountDate' => null,
                ],
                [
                    'vendorBillId' => 'bill-2',
                    'vendorBillNumber' => 'VB-002',
                    'vendorId' => 'vendor-1',
                    'vendorName' => 'Vendor One',
                    'amountCents' => 1250,
                    'discountCents' => 250,
                    'netAmountCents' => 1000,
                    'dueDate' => $today,
                    'discountDate' => null,
                ],
            ],
        );
    }
}
