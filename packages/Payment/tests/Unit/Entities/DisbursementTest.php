<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Entities;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Entities\Disbursement;
use Nexus\Payment\Enums\DisbursementStatus;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\ValueObjects\RecipientInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Disbursement::class)]
final class DisbursementTest extends TestCase
{
    #[Test]
    public function it_can_be_created_via_constructor(): void
    {
        $amount = Money::of(5000, 'USD');
        $recipient = RecipientInfo::create('vendor-001', 'Acme Corp');
        $createdAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $disbursement = new Disbursement(
            id: 'dsb-001',
            tenantId: 'tenant-001',
            referenceNumber: 'DSB-2024-001',
            amount: $amount,
            recipient: $recipient,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            createdBy: 'user-001',
            requiresApproval: true,
            createdAt: $createdAt,
            sourceDocumentIds: ['doc-001', 'doc-002'],
        );

        $this->assertSame('dsb-001', $disbursement->getId());
        $this->assertSame('tenant-001', $disbursement->getTenantId());
        $this->assertSame('DSB-2024-001', $disbursement->getReferenceNumber());
        $this->assertTrue($disbursement->getAmount()->equals($amount));
        $this->assertSame($recipient, $disbursement->getRecipient());
        $this->assertSame(PaymentMethodType::BANK_ACCOUNT, $disbursement->getMethodType());
        $this->assertSame('user-001', $disbursement->getCreatedBy());
        $this->assertSame(DisbursementStatus::DRAFT, $disbursement->getStatus());
        $this->assertSame(['doc-001', 'doc-002'], $disbursement->getSourceDocumentIds());
    }

    #[Test]
    public function it_can_be_created_via_factory_method(): void
    {
        $amount = Money::of(3000, 'USD');
        $recipient = RecipientInfo::create('vendor-002', 'Beta Inc');
        $scheduledDate = new \DateTimeImmutable('+7 days');

        $disbursement = Disbursement::create(
            id: 'dsb-002',
            tenantId: 'tenant-001',
            referenceNumber: 'DSB-2024-002',
            amount: $amount,
            recipient: $recipient,
            methodType: PaymentMethodType::BANK_ACCOUNT,
            createdBy: 'user-001',
            options: [
                'requires_approval' => false,
                'source_document_ids' => ['doc-003'],
                'scheduled_date' => $scheduledDate,
                'metadata' => ['notes' => 'Monthly payment'],
            ],
        );

        $this->assertSame('dsb-002', $disbursement->getId());
        $this->assertSame(DisbursementStatus::APPROVED, $disbursement->getStatus());
        $this->assertSame($scheduledDate, $disbursement->getScheduledDate());
        $this->assertSame('Monthly payment', $disbursement->getMetadata()['notes']);
    }

    #[Test]
    public function it_starts_in_draft_when_requires_approval(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: true);

        $this->assertSame(DisbursementStatus::DRAFT, $disbursement->getStatus());
        $this->assertTrue($disbursement->requiresApproval());
    }

    #[Test]
    public function it_starts_in_approved_when_no_approval_required(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: false);

        $this->assertSame(DisbursementStatus::APPROVED, $disbursement->getStatus());
        $this->assertFalse($disbursement->requiresApproval());
    }

    #[Test]
    public function it_can_submit_for_approval(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: true);

        $disbursement->submitForApproval();

        $this->assertSame(DisbursementStatus::PENDING_APPROVAL, $disbursement->getStatus());
    }

    #[Test]
    public function it_can_be_approved(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: true);
        $disbursement->submitForApproval();

        $disbursement->approve('approver-001', 'Looks good');

        $this->assertSame(DisbursementStatus::APPROVED, $disbursement->getStatus());
        $this->assertSame('approver-001', $disbursement->getApprovedBy());
        $this->assertInstanceOf(\DateTimeImmutable::class, $disbursement->getApprovedAt());
        $this->assertSame('Looks good', $disbursement->getMetadata()['approval_notes']);
    }

    #[Test]
    public function it_can_be_rejected(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: true);
        $disbursement->submitForApproval();

        $disbursement->reject('rejector-001', 'Insufficient documentation');

        $this->assertSame(DisbursementStatus::REJECTED, $disbursement->getStatus());
        $this->assertSame('rejector-001', $disbursement->getRejectedBy());
        $this->assertSame('Insufficient documentation', $disbursement->getRejectionReason());
        $this->assertInstanceOf(\DateTimeImmutable::class, $disbursement->getRejectedAt());
    }

    #[Test]
    public function it_can_transition_to_processing(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: false);

        $disbursement->markAsProcessing();

        $this->assertSame(DisbursementStatus::PROCESSING, $disbursement->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $disbursement->getProcessedAt());
    }

    #[Test]
    public function it_can_transition_to_completed(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: false);
        $disbursement->markAsProcessing();

        $disbursement->markAsCompleted('txn-001');

        $this->assertSame(DisbursementStatus::COMPLETED, $disbursement->getStatus());
        $this->assertSame('txn-001', $disbursement->getPaymentTransactionId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $disbursement->getCompletedAt());
        $this->assertTrue($disbursement->isCompleted());
    }

    #[Test]
    public function it_can_transition_to_failed(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: false);
        $disbursement->markAsProcessing();

        $disbursement->markAsFailed('BANK_REJECTED', 'Invalid account number');

        $this->assertSame(DisbursementStatus::FAILED, $disbursement->getStatus());
        $this->assertSame('BANK_REJECTED', $disbursement->getMetadata()['failure_code']);
        $this->assertSame('Invalid account number', $disbursement->getMetadata()['failure_message']);
        $this->assertTrue($disbursement->isFailed());
    }

    #[Test]
    public function it_can_be_cancelled(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: true);

        $disbursement->cancel('No longer needed');

        $this->assertSame(DisbursementStatus::CANCELLED, $disbursement->getStatus());
        $this->assertSame('No longer needed', $disbursement->getMetadata()['cancellation_reason']);
        $this->assertTrue($disbursement->isCancelled());
    }

    #[Test]
    public function it_throws_exception_for_invalid_transition(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: false);
        $disbursement->markAsProcessing();
        $disbursement->markAsCompleted('txn-001');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot transition from completed to processing');

        $disbursement->markAsProcessing();
    }

    #[Test]
    public function it_checks_if_ready_for_processing(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: false);

        $this->assertTrue($disbursement->isReadyForProcessing());
    }

    #[Test]
    public function it_is_not_ready_for_processing_when_not_approved(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: true);

        $this->assertFalse($disbursement->isReadyForProcessing());
    }

    #[Test]
    public function it_is_not_ready_for_processing_when_scheduled_in_future(): void
    {
        $disbursement = Disbursement::create(
            id: 'dsb-future',
            tenantId: 'tenant-001',
            referenceNumber: 'DSB-2024-100',
            amount: Money::of(1000, 'USD'),
            recipient: RecipientInfo::create('vendor-001', 'Vendor'),
            methodType: PaymentMethodType::BANK_ACCOUNT,
            createdBy: 'user-001',
            options: [
                'requires_approval' => false,
                'scheduled_date' => new \DateTimeImmutable('+7 days'),
            ],
        );

        $this->assertFalse($disbursement->isReadyForProcessing());
    }

    #[Test]
    public function it_can_check_transition_validity(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: true);

        $this->assertTrue($disbursement->canTransitionTo(DisbursementStatus::PENDING_APPROVAL));
        $this->assertTrue($disbursement->canTransitionTo(DisbursementStatus::CANCELLED));
        $this->assertFalse($disbursement->canTransitionTo(DisbursementStatus::COMPLETED));
    }

    #[Test]
    public function it_can_link_source_documents(): void
    {
        $disbursement = $this->createDisbursement();

        $disbursement->linkSourceDocuments(['doc-001', 'doc-002']);
        $disbursement->linkSourceDocuments(['doc-002', 'doc-003']); // doc-002 is duplicate

        $documents = $disbursement->getSourceDocumentIds();
        $this->assertCount(3, $documents);
        $this->assertContains('doc-001', $documents);
        $this->assertContains('doc-002', $documents);
        $this->assertContains('doc-003', $documents);
    }

    #[Test]
    public function it_can_schedule_for_future(): void
    {
        $disbursement = $this->createDisbursement(requiresApproval: false);
        $scheduleDate = new \DateTimeImmutable('+14 days');

        $disbursement->schedule($scheduleDate);

        $this->assertSame($scheduleDate, $disbursement->getScheduledDate());
    }

    #[Test]
    public function it_can_add_metadata(): void
    {
        $disbursement = $this->createDisbursement();

        $disbursement->addMetadata(['key1' => 'value1']);
        $disbursement->addMetadata(['key2' => 'value2']);

        $metadata = $disbursement->getMetadata();
        $this->assertSame('value1', $metadata['key1']);
        $this->assertSame('value2', $metadata['key2']);
    }

    private function createDisbursement(bool $requiresApproval = true): Disbursement
    {
        return Disbursement::create(
            id: 'dsb-test',
            tenantId: 'tenant-001',
            referenceNumber: 'DSB-2024-TEST',
            amount: Money::of(5000, 'USD'),
            recipient: RecipientInfo::create('vendor-001', 'Test Vendor'),
            methodType: PaymentMethodType::BANK_ACCOUNT,
            createdBy: 'user-001',
            options: [
                'requires_approval' => $requiresApproval,
            ],
        );
    }
}
