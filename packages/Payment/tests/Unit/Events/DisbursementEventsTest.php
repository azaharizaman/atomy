<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Events;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Events\DisbursementApprovedEvent;
use Nexus\Payment\Events\DisbursementCancelledEvent;
use Nexus\Payment\Events\DisbursementCompletedEvent;
use Nexus\Payment\Events\DisbursementCreatedEvent;
use Nexus\Payment\Events\DisbursementEvent;
use Nexus\Payment\Events\DisbursementFailedEvent;
use Nexus\Payment\Events\DisbursementRejectedEvent;
use Nexus\Payment\ValueObjects\RecipientInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisbursementEvent::class)]
#[CoversClass(DisbursementCreatedEvent::class)]
#[CoversClass(DisbursementApprovedEvent::class)]
#[CoversClass(DisbursementRejectedEvent::class)]
#[CoversClass(DisbursementCompletedEvent::class)]
#[CoversClass(DisbursementFailedEvent::class)]
#[CoversClass(DisbursementCancelledEvent::class)]
final class DisbursementEventsTest extends TestCase
{
    private DateTimeImmutable $occurredAt;

    protected function setUp(): void
    {
        $this->occurredAt = new DateTimeImmutable('2024-01-15 10:30:00');
    }

    // ========================================================================
    // DisbursementCreatedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_disbursement_created_event(): void
    {
        $amount = Money::of(5000.00, 'MYR');
        $recipient = RecipientInfo::create('vendor-001', 'Acme Supplies Sdn Bhd');

        $event = new DisbursementCreatedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            referenceNumber: 'DISB-2024-0001',
            amount: $amount,
            recipient: $recipient,
            createdBy: 'user-001',
            sourceDocumentIds: ['INV-001', 'INV-002'],
            occurredAt: $this->occurredAt
        );

        $this->assertSame('disb-001', $event->disbursementId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame('DISB-2024-0001', $event->referenceNumber);
        $this->assertSame($amount, $event->amount);
        $this->assertSame($recipient, $event->recipient);
        $this->assertSame('user-001', $event->createdBy);
        $this->assertSame(['INV-001', 'INV-002'], $event->sourceDocumentIds);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_disbursement_created_event_extends_disbursement_event(): void
    {
        $event = new DisbursementCreatedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            referenceNumber: 'DISB-001',
            amount: Money::of(100.00, 'MYR'),
            recipient: RecipientInfo::create('vendor-001', 'Test Vendor'),
            createdBy: 'system',
            sourceDocumentIds: [],
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(DisbursementEvent::class, $event);
    }

    #[Test]
    public function it_disbursement_created_with_empty_source_documents(): void
    {
        $event = new DisbursementCreatedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            referenceNumber: 'DISB-001',
            amount: Money::of(1000.00, 'MYR'),
            recipient: RecipientInfo::create('emp-001', 'John Doe'),
            createdBy: 'hr-manager',
            sourceDocumentIds: [],
            occurredAt: $this->occurredAt
        );

        $this->assertEmpty($event->sourceDocumentIds);
    }

    #[Test]
    public function it_disbursement_created_with_multiple_source_documents(): void
    {
        $sourceDocuments = ['PO-001', 'GRN-001', 'INV-001', 'INV-002', 'INV-003'];

        $event = new DisbursementCreatedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            referenceNumber: 'DISB-001',
            amount: Money::of(25000.00, 'MYR'),
            recipient: RecipientInfo::create('vendor-001', 'Big Supplier Ltd'),
            createdBy: 'ap-clerk-001',
            sourceDocumentIds: $sourceDocuments,
            occurredAt: $this->occurredAt
        );

        $this->assertCount(5, $event->sourceDocumentIds);
        $this->assertContains('PO-001', $event->sourceDocumentIds);
        $this->assertContains('INV-003', $event->sourceDocumentIds);
    }

    #[Test]
    public function it_disbursement_created_with_recipient_having_account(): void
    {
        $recipient = RecipientInfo::withAccount(
            'vendor-001',
            'Acme Corp',
            'bank-account-001'
        );

        $event = new DisbursementCreatedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            referenceNumber: 'DISB-001',
            amount: Money::of(5000.00, 'MYR'),
            recipient: $recipient,
            createdBy: 'user-001',
            sourceDocumentIds: ['INV-001'],
            occurredAt: $this->occurredAt
        );

        $this->assertTrue($event->recipient->hasAccount());
        $this->assertSame('bank-account-001', $event->recipient->accountId);
    }

    // ========================================================================
    // DisbursementApprovedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_disbursement_approved_event(): void
    {
        $amount = Money::of(5000.00, 'MYR');
        $approvedAt = new DateTimeImmutable('2024-01-15 14:00:00');

        $event = new DisbursementApprovedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: $amount,
            approvedBy: 'approver-001',
            notes: 'Approved for payment in next batch',
            approvedAt: $approvedAt,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('disb-001', $event->disbursementId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame('approver-001', $event->approvedBy);
        $this->assertSame('Approved for payment in next batch', $event->notes);
        $this->assertSame($approvedAt, $event->approvedAt);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_disbursement_approved_event_extends_disbursement_event(): void
    {
        $event = new DisbursementApprovedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            approvedBy: 'approver-001',
            notes: null,
            approvedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(DisbursementEvent::class, $event);
    }

    #[Test]
    public function it_disbursement_approved_with_null_notes(): void
    {
        $event = new DisbursementApprovedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(2500.00, 'MYR'),
            approvedBy: 'cfo-001',
            notes: null,
            approvedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertNull($event->notes);
    }

    #[Test]
    public function it_disbursement_approved_with_long_notes(): void
    {
        $longNotes = str_repeat('This is a detailed approval note with context. ', 20);

        $event = new DisbursementApprovedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(10000.00, 'MYR'),
            approvedBy: 'finance-director',
            notes: $longNotes,
            approvedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertSame($longNotes, $event->notes);
    }

    // ========================================================================
    // DisbursementRejectedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_disbursement_rejected_event(): void
    {
        $amount = Money::of(5000.00, 'MYR');
        $rejectedAt = new DateTimeImmutable('2024-01-15 14:30:00');

        $event = new DisbursementRejectedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: $amount,
            rejectedBy: 'approver-001',
            reason: 'Missing supporting documents',
            rejectedAt: $rejectedAt,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('disb-001', $event->disbursementId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame('approver-001', $event->rejectedBy);
        $this->assertSame('Missing supporting documents', $event->reason);
        $this->assertSame($rejectedAt, $event->rejectedAt);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_disbursement_rejected_event_extends_disbursement_event(): void
    {
        $event = new DisbursementRejectedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            rejectedBy: 'approver-001',
            reason: 'Budget exceeded',
            rejectedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(DisbursementEvent::class, $event);
    }

    #[Test]
    public function it_disbursement_rejected_with_detailed_reason(): void
    {
        $detailedReason = 'Payment cannot be processed because: 1) Invoice date is beyond the acceptable range, 2) Vendor is on hold status, 3) Amount exceeds single-transaction limit';

        $event = new DisbursementRejectedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(100000.00, 'MYR'),
            rejectedBy: 'compliance-officer',
            reason: $detailedReason,
            rejectedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertSame($detailedReason, $event->reason);
    }

    // ========================================================================
    // DisbursementCompletedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_disbursement_completed_event(): void
    {
        $amount = Money::of(5000.00, 'MYR');
        $completedAt = new DateTimeImmutable('2024-01-16 09:00:00');

        $event = new DisbursementCompletedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: $amount,
            paymentTransactionId: 'payment-txn-001',
            completedAt: $completedAt,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('disb-001', $event->disbursementId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame('payment-txn-001', $event->paymentTransactionId);
        $this->assertSame($completedAt, $event->completedAt);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_disbursement_completed_event_extends_disbursement_event(): void
    {
        $event = new DisbursementCompletedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            paymentTransactionId: 'txn-001',
            completedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(DisbursementEvent::class, $event);
    }

    #[Test]
    public function it_disbursement_completed_with_external_payment_reference(): void
    {
        $event = new DisbursementCompletedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(15000.00, 'MYR'),
            paymentTransactionId: 'BANK-TXN-2024-ABC123',
            completedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertSame('BANK-TXN-2024-ABC123', $event->paymentTransactionId);
    }

    #[Test]
    public function it_disbursement_completed_with_large_amount(): void
    {
        $event = new DisbursementCompletedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(999999.99, 'MYR'),
            paymentTransactionId: 'large-txn-001',
            completedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertEqualsWithDelta(999999.99, $event->amount->getAmount(), 0.001);
    }

    // ========================================================================
    // DisbursementFailedEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_disbursement_failed_event(): void
    {
        $amount = Money::of(5000.00, 'MYR');

        $event = new DisbursementFailedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: $amount,
            failureCode: 'bank_account_closed',
            failureMessage: 'The recipient bank account has been closed',
            isRetryable: false,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('disb-001', $event->disbursementId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame('bank_account_closed', $event->failureCode);
        $this->assertSame('The recipient bank account has been closed', $event->failureMessage);
        $this->assertFalse($event->isRetryable);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_disbursement_failed_event_extends_disbursement_event(): void
    {
        $event = new DisbursementFailedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            failureCode: 'error',
            failureMessage: 'Error occurred',
            isRetryable: true,
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(DisbursementEvent::class, $event);
    }

    #[Test]
    public function it_disbursement_failed_with_retryable_error(): void
    {
        $event = new DisbursementFailedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(3000.00, 'MYR'),
            failureCode: 'bank_timeout',
            failureMessage: 'Bank gateway timeout - please retry',
            isRetryable: true,
            occurredAt: $this->occurredAt
        );

        $this->assertTrue($event->isRetryable);
    }

    #[Test]
    public function it_disbursement_failed_with_non_retryable_error(): void
    {
        $event = new DisbursementFailedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(3000.00, 'MYR'),
            failureCode: 'invalid_account_number',
            failureMessage: 'The recipient account number is invalid',
            isRetryable: false,
            occurredAt: $this->occurredAt
        );

        $this->assertFalse($event->isRetryable);
    }

    #[Test]
    public function it_disbursement_failed_with_various_failure_codes(): void
    {
        $failureCodes = [
            'insufficient_funds' => 'Not enough funds in source account',
            'account_frozen' => 'Recipient account is frozen',
            'exceeds_daily_limit' => 'Transaction exceeds daily limit',
            'invalid_routing' => 'Invalid bank routing number',
        ];

        foreach ($failureCodes as $code => $message) {
            $event = new DisbursementFailedEvent(
                disbursementId: 'disb-001',
                tenantId: 'tenant-001',
                amount: Money::of(1000.00, 'MYR'),
                failureCode: $code,
                failureMessage: $message,
                isRetryable: $code === 'insufficient_funds',
                occurredAt: $this->occurredAt
            );

            $this->assertSame($code, $event->failureCode);
            $this->assertSame($message, $event->failureMessage);
        }
    }

    // ========================================================================
    // DisbursementCancelledEvent Tests
    // ========================================================================

    #[Test]
    public function it_creates_disbursement_cancelled_event(): void
    {
        $amount = Money::of(5000.00, 'MYR');
        $cancelledAt = new DateTimeImmutable('2024-01-15 16:00:00');

        $event = new DisbursementCancelledEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: $amount,
            cancelledBy: 'user-001',
            reason: 'Vendor requested payment hold',
            cancelledAt: $cancelledAt,
            occurredAt: $this->occurredAt
        );

        $this->assertSame('disb-001', $event->disbursementId);
        $this->assertSame('tenant-001', $event->tenantId);
        $this->assertSame($amount, $event->amount);
        $this->assertSame('user-001', $event->cancelledBy);
        $this->assertSame('Vendor requested payment hold', $event->reason);
        $this->assertSame($cancelledAt, $event->cancelledAt);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }

    #[Test]
    public function it_disbursement_cancelled_event_extends_disbursement_event(): void
    {
        $event = new DisbursementCancelledEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            cancelledBy: 'system',
            reason: 'Timeout',
            cancelledAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertInstanceOf(DisbursementEvent::class, $event);
    }

    #[Test]
    public function it_disbursement_cancelled_by_system(): void
    {
        $event = new DisbursementCancelledEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(2000.00, 'MYR'),
            cancelledBy: 'system',
            reason: 'Disbursement expired after 30 days without approval',
            cancelledAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertSame('system', $event->cancelledBy);
    }

    #[Test]
    public function it_disbursement_cancelled_with_detailed_reason(): void
    {
        $detailedReason = 'Cancelled due to duplicate payment detection. Original disbursement DISB-2024-0001 was already processed for the same invoices.';

        $event = new DisbursementCancelledEvent(
            disbursementId: 'disb-002',
            tenantId: 'tenant-001',
            amount: Money::of(7500.00, 'MYR'),
            cancelledBy: 'duplicate-detection-service',
            reason: $detailedReason,
            cancelledAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $this->assertSame($detailedReason, $event->reason);
    }

    // ========================================================================
    // Property Immutability Tests
    // ========================================================================

    #[Test]
    public function it_disbursement_created_event_properties_are_readonly(): void
    {
        $event = new DisbursementCreatedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            referenceNumber: 'DISB-001',
            amount: Money::of(100.00, 'MYR'),
            recipient: RecipientInfo::create('vendor-001', 'Test'),
            createdBy: 'user-001',
            sourceDocumentIds: ['INV-001'],
            occurredAt: $this->occurredAt
        );

        $reflection = new \ReflectionClass($event);
        
        $this->assertTrue($reflection->getProperty('referenceNumber')->isReadOnly());
        $this->assertTrue($reflection->getProperty('amount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('recipient')->isReadOnly());
        $this->assertTrue($reflection->getProperty('createdBy')->isReadOnly());
        $this->assertTrue($reflection->getProperty('sourceDocumentIds')->isReadOnly());
    }

    #[Test]
    public function it_disbursement_approved_event_properties_are_readonly(): void
    {
        $event = new DisbursementApprovedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            approvedBy: 'approver-001',
            notes: 'Approved',
            approvedAt: new DateTimeImmutable(),
            occurredAt: $this->occurredAt
        );

        $reflection = new \ReflectionClass($event);
        
        $this->assertTrue($reflection->getProperty('amount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('approvedBy')->isReadOnly());
        $this->assertTrue($reflection->getProperty('notes')->isReadOnly());
        $this->assertTrue($reflection->getProperty('approvedAt')->isReadOnly());
    }

    #[Test]
    public function it_disbursement_failed_event_properties_are_readonly(): void
    {
        $event = new DisbursementFailedEvent(
            disbursementId: 'disb-001',
            tenantId: 'tenant-001',
            amount: Money::of(100.00, 'MYR'),
            failureCode: 'error',
            failureMessage: 'Error',
            isRetryable: false,
            occurredAt: $this->occurredAt
        );

        $reflection = new \ReflectionClass($event);
        
        $this->assertTrue($reflection->getProperty('amount')->isReadOnly());
        $this->assertTrue($reflection->getProperty('failureCode')->isReadOnly());
        $this->assertTrue($reflection->getProperty('failureMessage')->isReadOnly());
        $this->assertTrue($reflection->getProperty('isRetryable')->isReadOnly());
    }

    // ========================================================================
    // Base DisbursementEvent Tests
    // ========================================================================

    #[Test]
    public function it_base_event_properties_inherited_correctly(): void
    {
        $event = new DisbursementCreatedEvent(
            disbursementId: 'disb-abc-123',
            tenantId: 'tenant-xyz-789',
            referenceNumber: 'DISB-001',
            amount: Money::of(100.00, 'MYR'),
            recipient: RecipientInfo::create('vendor-001', 'Test'),
            createdBy: 'user-001',
            sourceDocumentIds: [],
            occurredAt: $this->occurredAt
        );

        // Test inherited properties from DisbursementEvent base class
        $this->assertSame('disb-abc-123', $event->disbursementId);
        $this->assertSame('tenant-xyz-789', $event->tenantId);
        $this->assertSame($this->occurredAt, $event->occurredAt);
    }
}
