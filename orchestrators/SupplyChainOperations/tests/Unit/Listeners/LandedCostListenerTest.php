<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Listeners;

use Nexus\Payable\Events\InvoiceApprovedForPaymentEvent;
use Nexus\Payable\Contracts\VendorBillInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\SupplyChainOperations\Contracts\LandedCostCoordinatorInterface;
use Nexus\SupplyChainOperations\Listeners\LandedCostListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LandedCostListenerTest extends TestCase
{
    private VendorBillRepositoryInterface $billRepository;
    private LandedCostCoordinatorInterface $coordinator;
    private LoggerInterface $logger;
    private LandedCostListener $listener;

    protected function setUp(): void
    {
        $this->billRepository = $this->createMock(VendorBillRepositoryInterface::class);
        $this->coordinator = $this->createMock(LandedCostCoordinatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new LandedCostListener(
            $this->billRepository,
            $this->coordinator,
            $this->logger
        );
    }

    public function test_on_invoice_approved_distributes_landed_cost(): void
    {
        $event = new InvoiceApprovedForPaymentEvent('bill-001', 'tenant-001');

        $bill = $this->createMock(VendorBillInterface::class);
        $bill->method('getDescription')->willReturn('Landed Cost: GRN-001');
        $bill->method('getTotalAmount')->willReturn(150.0);
        $bill->method('getBillNumber')->willReturn('BILL-001');

        $this->billRepository
            ->expects($this->once())
            ->method('findById')
            ->with('bill-001')
            ->willReturn($bill);

        $this->coordinator
            ->expects($this->once())
            ->method('distributeLandedCost')
            ->with('GRN-001', 150.0, 'value');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Landed Cost detected'));

        $this->listener->onInvoiceApproved($event);
    }

    public function test_on_invoice_approved_skips_non_landed_cost_bills(): void
    {
        $event = new InvoiceApprovedForPaymentEvent('bill-002', 'tenant-001');

        $bill = $this->createMock(VendorBillInterface::class);
        $bill->method('getDescription')->willReturn('Regular invoice for services');

        $this->billRepository
            ->expects($this->once())
            ->method('findById')
            ->with('bill-002')
            ->willReturn($bill);

        $this->coordinator
            ->expects($this->never())
            ->method('distributeLandedCost');

        $this->listener->onInvoiceApproved($event);
    }

    public function test_on_invoice_approved_handles_missing_bill(): void
    {
        $event = new InvoiceApprovedForPaymentEvent('bill-invalid', 'tenant-001');

        $this->billRepository
            ->expects($this->once())
            ->method('findById')
            ->with('bill-invalid')
            ->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('not found'));

        $this->coordinator
            ->expects($this->never())
            ->method('distributeLandedCost');

        $this->listener->onInvoiceApproved($event);
    }

    public function test_on_invoice_approved_handles_empty_description(): void
    {
        $event = new InvoiceApprovedForPaymentEvent('bill-003', 'tenant-001');

        $bill = $this->createMock(VendorBillInterface::class);
        $bill->method('getDescription')->willReturn('');

        $this->billRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($bill);

        $this->coordinator
            ->expects($this->never())
            ->method('distributeLandedCost');

        $this->listener->onInvoiceApproved($event);
    }
}
