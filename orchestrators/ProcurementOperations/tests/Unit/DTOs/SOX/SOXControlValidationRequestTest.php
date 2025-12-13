<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\SOX;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationRequest;
use Nexus\ProcurementOperations\Enums\P2PStep;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SOXControlValidationRequest::class)]
final class SOXControlValidationRequestTest extends TestCase
{
    #[Test]
    public function forRequisition_creates_requisition_request(): void
    {
        $request = SOXControlValidationRequest::forRequisition(
            tenantId: 'tenant-123',
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(5000, 'USD'),
            budgetAvailable: Money::of(10000, 'USD'),
            approvers: ['approver-001', 'approver-002'],
        );

        $this->assertEquals(P2PStep::REQUISITION, $request->step);
        $this->assertEquals('tenant-123', $request->tenantId);
        $this->assertEquals('req-001', $request->entityId);
        $this->assertEquals('user-001', $request->userId);
        $this->assertEquals(500000, $request->amount->getAmountInCents());
        $this->assertEquals(1000000, $request->budgetAvailable?->getAmountInCents());
        $this->assertCount(2, $request->approvers);
    }

    #[Test]
    public function forPOCreation_creates_po_request(): void
    {
        $request = SOXControlValidationRequest::forPOCreation(
            tenantId: 'tenant-123',
            entityId: 'po-001',
            userId: 'user-001',
            amount: Money::of(10000, 'USD'),
            vendorId: 'vendor-001',
            contractPrice: Money::of(9500, 'USD'),
            requisitionId: 'req-001',
        );

        $this->assertEquals(P2PStep::PO_CREATION, $request->step);
        $this->assertEquals('vendor-001', $request->vendorId);
        $this->assertEquals(950000, $request->contractPrice?->getAmountInCents());
        $this->assertEquals('req-001', $request->requisitionId);
    }

    #[Test]
    public function forGoodsReceipt_creates_gr_request(): void
    {
        $request = SOXControlValidationRequest::forGoodsReceipt(
            tenantId: 'tenant-123',
            entityId: 'gr-001',
            userId: 'user-001',
            poId: 'po-001',
            poQuantity: 100.0,
            receivedQuantity: 98.0,
        );

        $this->assertEquals(P2PStep::GOODS_RECEIPT, $request->step);
        $this->assertEquals('po-001', $request->poId);
        $this->assertEquals(100.0, $request->poQuantity);
        $this->assertEquals(98.0, $request->receivedQuantity);
    }

    #[Test]
    public function forInvoiceMatch_creates_invoice_request(): void
    {
        $request = SOXControlValidationRequest::forInvoiceMatch(
            tenantId: 'tenant-123',
            entityId: 'inv-001',
            userId: 'user-001',
            invoiceAmount: Money::of(10000, 'USD'),
            vendorId: 'vendor-001',
            poAmount: Money::of(10000, 'USD'),
            grAmount: Money::of(10000, 'USD'),
        );

        $this->assertEquals(P2PStep::INVOICE_MATCH, $request->step);
        $this->assertEquals('inv-001', $request->entityId);
        $this->assertEquals(1000000, $request->invoiceAmount?->getAmountInCents());
        $this->assertEquals(1000000, $request->poAmount?->getAmountInCents());
        $this->assertEquals(1000000, $request->grAmount?->getAmountInCents());
    }

    #[Test]
    public function forPayment_creates_payment_request(): void
    {
        $request = SOXControlValidationRequest::forPayment(
            tenantId: 'tenant-123',
            entityId: 'pay-001',
            userId: 'user-001',
            amount: Money::of(10000, 'USD'),
            vendorId: 'vendor-001',
            invoiceId: 'inv-001',
            paymentApprovers: ['manager-001', 'cfo-001'],
        );

        $this->assertEquals(P2PStep::PAYMENT, $request->step);
        $this->assertEquals('pay-001', $request->entityId);
        $this->assertEquals('inv-001', $request->invoiceId);
        $this->assertCount(2, $request->paymentApprovers);
    }

    #[Test]
    public function getContext_returns_step_specific_context(): void
    {
        $request = SOXControlValidationRequest::forRequisition(
            tenantId: 'tenant-123',
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(5000, 'USD'),
            budgetAvailable: Money::of(10000, 'USD'),
            approvers: ['approver-001'],
        );

        $context = $request->getContext();

        $this->assertIsArray($context);
        $this->assertArrayHasKey('step', $context);
        $this->assertArrayHasKey('entity_id', $context);
        $this->assertArrayHasKey('user_id', $context);
        $this->assertEquals(P2PStep::REQUISITION->value, $context['step']);
    }

    #[Test]
    public function withMetadata_adds_additional_data(): void
    {
        $request = SOXControlValidationRequest::forRequisition(
            tenantId: 'tenant-123',
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(5000, 'USD'),
        );

        $requestWithMeta = $request->withMetadata([
            'department' => 'IT',
            'cost_center' => 'CC-001',
        ]);

        $this->assertArrayHasKey('department', $requestWithMeta->metadata);
        $this->assertEquals('IT', $requestWithMeta->metadata['department']);
    }

    #[Test]
    public function toArray_returns_serializable_data(): void
    {
        $request = SOXControlValidationRequest::forPOCreation(
            tenantId: 'tenant-123',
            entityId: 'po-001',
            userId: 'user-001',
            amount: Money::of(10000, 'USD'),
            vendorId: 'vendor-001',
        );

        $array = $request->toArray();

        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('entity_id', $array);
        $this->assertArrayHasKey('step', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertEquals('tenant-123', $array['tenant_id']);
        $this->assertEquals(P2PStep::PO_CREATION->value, $array['step']);
    }
}
