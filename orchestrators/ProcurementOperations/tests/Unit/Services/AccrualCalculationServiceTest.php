<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\JournalEntry\Contracts\JournalEntryManagerInterface;
use Nexus\ProcurementOperations\Exceptions\AccrualException;
use Nexus\ProcurementOperations\Services\AccrualCalculationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(AccrualCalculationService::class)]
final class AccrualCalculationServiceTest extends TestCase
{
    #[Test]
    public function it_throws_when_goods_receipt_accrual_is_attempted_without_journal_entry_manager(): void
    {
        $service = new AccrualCalculationService(
            journalEntryManager: null,
            logger: new NullLogger(),
        );

        $this->expectException(AccrualException::class);
        $this->expectExceptionMessage('JournalEntryManager is not configured');

        $service->postGoodsReceiptAccrual(
            tenantId: 'TENANT-001',
            goodsReceiptId: 'GR-001',
            purchaseOrderId: 'PO-001',
            lineItems: [[
                'productId' => 'PROD-001',
                'quantity' => 2.0,
                'unitPriceCents' => 1000,
                'totalCents' => 2000,
            ]],
            postedBy: 'USER-001',
        );
    }

    #[Test]
    public function it_throws_when_reversal_is_attempted_without_goods_receipt_ids(): void
    {
        $service = new AccrualCalculationService(
            journalEntryManager: null,
            logger: new NullLogger(),
        );

        $this->expectException(AccrualException::class);
        $this->expectExceptionMessage('At least one goods receipt ID is required');

        $service->reverseAccrualOnMatch(
            tenantId: 'TENANT-001',
            vendorBillId: 'VB-001',
            goodsReceiptIds: [],
            postedBy: 'USER-001',
        );
    }

    #[Test]
    public function it_throws_when_reversal_is_not_implemented_without_journal_references(): void
    {
        $journalEntryManager = $this->createMock(JournalEntryManagerInterface::class);
        $service = new AccrualCalculationService(
            journalEntryManager: $journalEntryManager,
            logger: new NullLogger(),
        );

        $this->expectException(AccrualException::class);
        $this->expectExceptionMessage('not implemented');

        $service->reverseAccrualOnMatch(
            tenantId: 'TENANT-001',
            vendorBillId: 'VB-001',
            goodsReceiptIds: ['GR-001'],
            postedBy: 'USER-001',
        );
    }

    #[Test]
    public function it_throws_when_payable_liability_amount_is_zero(): void
    {
        $service = new AccrualCalculationService(
            journalEntryManager: null,
            logger: new NullLogger(),
        );

        $this->expectException(AccrualException::class);
        $this->expectExceptionMessage('Cannot post accrual for zero amount');

        $service->postPayableLiability(
            tenantId: 'TENANT-001',
            vendorBillId: 'VB-001',
            vendorId: 'VENDOR-001',
            amountCents: 0,
            currency: 'USD',
            postedBy: 'USER-001',
        );
    }

    #[Test]
    public function it_throws_when_payment_discount_is_negative(): void
    {
        $service = new AccrualCalculationService(
            journalEntryManager: null,
            logger: new NullLogger(),
        );

        $this->expectException(AccrualException::class);
        $this->expectExceptionMessage('Discount cannot be negative');

        $service->postPaymentEntry(
            tenantId: 'TENANT-001',
            paymentId: 'PAY-001',
            vendorId: 'VENDOR-001',
            amountCents: 1000,
            discountCents: -1,
            currency: 'USD',
            bankAccountId: 'BANK-001',
            postedBy: 'USER-001',
        );
    }

    #[Test]
    public function it_throws_when_payment_discount_exceeds_amount(): void
    {
        $service = new AccrualCalculationService(
            journalEntryManager: null,
            logger: new NullLogger(),
        );

        $this->expectException(AccrualException::class);
        $this->expectExceptionMessage('Discount cannot exceed payment amount');

        $service->postPaymentEntry(
            tenantId: 'TENANT-001',
            paymentId: 'PAY-001',
            vendorId: 'VENDOR-001',
            amountCents: 1000,
            discountCents: 1500,
            currency: 'USD',
            bankAccountId: 'BANK-001',
            postedBy: 'USER-001',
        );
    }
}
