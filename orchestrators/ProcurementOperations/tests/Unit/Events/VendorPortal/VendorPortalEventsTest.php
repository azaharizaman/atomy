<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Events\VendorPortal;

use Nexus\ProcurementOperations\Events\VendorPortal\VendorRegistrationSubmittedEvent;
use Nexus\ProcurementOperations\Events\VendorPortal\VendorOnboardingApprovedEvent;
use Nexus\ProcurementOperations\Events\VendorPortal\VendorOnboardingRejectedEvent;
use Nexus\ProcurementOperations\Events\VendorPortal\VendorProfileUpdatedEvent;
use Nexus\ProcurementOperations\Events\VendorPortal\VendorSuspendedEvent;
use Nexus\ProcurementOperations\Events\VendorPortal\VendorReactivatedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorRegistrationSubmittedEvent::class)]
#[CoversClass(VendorOnboardingApprovedEvent::class)]
#[CoversClass(VendorOnboardingRejectedEvent::class)]
#[CoversClass(VendorProfileUpdatedEvent::class)]
#[CoversClass(VendorSuspendedEvent::class)]
#[CoversClass(VendorReactivatedEvent::class)]
final class VendorPortalEventsTest extends TestCase
{
    // =========================================================================
    // VendorRegistrationSubmittedEvent Tests
    // =========================================================================

    #[Test]
    public function it_creates_registration_submitted_event(): void
    {
        $event = new VendorRegistrationSubmittedEvent(
            workflowId: 'WF-001',
            vendorName: 'Acme Corp',
            countryCode: 'MY',
            registrationEmail: 'contact@acme.com',
            vendorType: 'domestic',
            submittedAt: new \DateTimeImmutable('2024-01-15 10:00:00'),
        );

        $this->assertSame('WF-001', $event->workflowId);
        $this->assertSame('Acme Corp', $event->vendorName);
        $this->assertSame('MY', $event->countryCode);
        $this->assertSame('domestic', $event->vendorType);
    }

    #[Test]
    public function it_serializes_registration_event_to_array(): void
    {
        $event = new VendorRegistrationSubmittedEvent(
            workflowId: 'WF-001',
            vendorName: 'Test Vendor',
            countryCode: 'SG',
            registrationEmail: 'test@vendor.com',
            vendorType: 'foreign',
            submittedAt: new \DateTimeImmutable('2024-01-15'),
        );

        $array = $event->toArray();

        $this->assertSame('vendor.registration.submitted', $array['event_type']);
        $this->assertSame('WF-001', $array['workflow_id']);
        $this->assertSame('Test Vendor', $array['vendor_name']);
    }

    // =========================================================================
    // VendorOnboardingApprovedEvent Tests
    // =========================================================================

    #[Test]
    public function it_creates_onboarding_approved_event(): void
    {
        $event = new VendorOnboardingApprovedEvent(
            vendorId: 'VND-123',
            workflowId: 'WF-001',
            approvedBy: 'USR-APPROVER',
            tier: 'premium',
            effectiveDate: new \DateTimeImmutable('2024-02-01'),
            approvedAt: new \DateTimeImmutable('2024-01-20'),
            approvalNotes: 'All compliance checks passed',
        );

        $this->assertSame('VND-123', $event->vendorId);
        $this->assertSame('premium', $event->tier);
        $this->assertSame('USR-APPROVER', $event->approvedBy);
    }

    #[Test]
    public function it_serializes_approved_event_to_array(): void
    {
        $event = new VendorOnboardingApprovedEvent(
            vendorId: 'VND-456',
            workflowId: 'WF-002',
            approvedBy: 'USR-MGR',
            tier: 'basic',
            effectiveDate: new \DateTimeImmutable('2024-03-01'),
            approvedAt: new \DateTimeImmutable('2024-02-15'),
        );

        $array = $event->toArray();

        $this->assertSame('vendor.onboarding.approved', $array['event_type']);
        $this->assertSame('VND-456', $array['vendor_id']);
        $this->assertSame('basic', $array['tier']);
    }

    // =========================================================================
    // VendorOnboardingRejectedEvent Tests
    // =========================================================================

    #[Test]
    public function it_creates_onboarding_rejected_event(): void
    {
        $event = VendorOnboardingRejectedEvent::complianceFailure(
            vendorId: 'VND-789',
            workflowId: 'WF-003',
            rejectedBy: 'USR-COMPLIANCE',
            details: ['missing_certifications' => ['ISO 9001']],
        );

        $this->assertSame('VND-789', $event->vendorId);
        $this->assertSame('compliance_failure', $event->rejectionReason);
    }

    #[Test]
    public function it_creates_sanction_match_rejection(): void
    {
        $event = VendorOnboardingRejectedEvent::sanctionMatch(
            vendorId: 'VND-SANCTION',
            workflowId: 'WF-004',
            rejectedBy: 'SYSTEM',
            matchedList: 'OFAC SDN',
            matchConfidence: 95.5,
        );

        $this->assertSame('sanction_match', $event->rejectionReason);
        $this->assertTrue($event->isPermanent);
        $this->assertFalse($event->canReapply);
    }

    #[Test]
    public function it_creates_duplicate_vendor_rejection(): void
    {
        $event = VendorOnboardingRejectedEvent::duplicateVendor(
            vendorId: 'VND-DUP',
            workflowId: 'WF-005',
            rejectedBy: 'SYSTEM',
            existingVendorId: 'VND-EXISTING',
        );

        $this->assertSame('duplicate_vendor', $event->rejectionReason);
    }

    #[Test]
    public function it_creates_manual_rejection(): void
    {
        $event = VendorOnboardingRejectedEvent::manualRejection(
            vendorId: 'VND-MAN',
            workflowId: 'WF-006',
            rejectedBy: 'USR-MGR',
            reason: 'Does not meet quality standards',
            canReapply: true,
            reapplyAfterDays: 90,
        );

        $this->assertSame('manual', $event->rejectionReason);
        $this->assertTrue($event->canReapply);
        $this->assertSame(90, $event->reapplyAfterDays);
    }

    // =========================================================================
    // VendorProfileUpdatedEvent Tests
    // =========================================================================

    #[Test]
    public function it_creates_profile_updated_event(): void
    {
        $event = VendorProfileUpdatedEvent::create(
            vendorId: 'VND-123',
            updatedBy: 'USR-VENDOR',
            changedFields: ['address', 'phone'],
            previousValues: ['address' => '123 Old St', 'phone' => '+60111111111'],
            newValues: ['address' => '456 New Ave', 'phone' => '+60122222222'],
        );

        $this->assertSame('VND-123', $event->vendorId);
        $this->assertContains('address', $event->changedFields);
        $this->assertContains('phone', $event->changedFields);
    }

    #[Test]
    public function it_creates_banking_details_changed_event(): void
    {
        $event = VendorProfileUpdatedEvent::bankingDetailsChanged(
            vendorId: 'VND-456',
            updatedBy: 'USR-VENDOR',
            previousBankCode: 'MBBEMYKL',
            newBankCode: 'CIABORJ1',
        );

        $this->assertContains('banking_details', $event->changedFields);
        $this->assertTrue($event->requiresApproval);
    }

    #[Test]
    public function it_creates_contact_changed_event(): void
    {
        $event = VendorProfileUpdatedEvent::contactChanged(
            vendorId: 'VND-789',
            updatedBy: 'USR-ADMIN',
            contactType: 'primary',
            previousContact: ['name' => 'John', 'email' => 'john@old.com'],
            newContact: ['name' => 'Jane', 'email' => 'jane@new.com'],
        );

        $this->assertContains('contact.primary', $event->changedFields);
    }

    // =========================================================================
    // VendorSuspendedEvent Tests
    // =========================================================================

    #[Test]
    public function it_creates_compliance_violation_suspension(): void
    {
        $event = VendorSuspendedEvent::complianceViolation(
            vendorId: 'VND-COMP',
            suspendedBy: 'USR-COMPLIANCE',
            violationType: 'expired_certification',
            details: ['certification' => 'ISO 9001', 'expired_on' => '2024-01-01'],
        );

        $this->assertSame('VND-COMP', $event->vendorId);
        $this->assertSame('compliance_violation', $event->suspensionReason);
    }

    #[Test]
    public function it_creates_quality_issues_suspension(): void
    {
        $event = VendorSuspendedEvent::qualityIssues(
            vendorId: 'VND-QUAL',
            suspendedBy: 'USR-QC',
            qualityScore: 45.0,
            rejectionRate: 25.0,
        );

        $this->assertSame('quality_issues', $event->suspensionReason);
        $this->assertNotNull($event->suspendedUntil); // Auto-calculates review date
    }

    #[Test]
    public function it_creates_payment_issues_suspension(): void
    {
        $event = VendorSuspendedEvent::paymentIssues(
            vendorId: 'VND-PAY',
            suspendedBy: 'SYSTEM',
            overdueAmount: 50000.00,
            overdueDays: 90,
        );

        $this->assertSame('payment_issues', $event->suspensionReason);
    }

    #[Test]
    public function it_creates_sanctions_match_suspension(): void
    {
        $event = VendorSuspendedEvent::sanctionsMatch(
            vendorId: 'VND-SANC',
            suspendedBy: 'SYSTEM',
            matchedList: 'EU Sanctions',
            matchConfidence: 88.5,
        );

        $this->assertSame('sanctions_match', $event->suspensionReason);
        $this->assertTrue($event->isPermanent);
    }

    #[Test]
    public function it_creates_administrative_suspension(): void
    {
        $event = VendorSuspendedEvent::administrative(
            vendorId: 'VND-ADMIN',
            suspendedBy: 'USR-ADMIN',
            reason: 'Contract renegotiation pending',
            reviewDate: new \DateTimeImmutable('+30 days'),
        );

        $this->assertSame('administrative', $event->suspensionReason);
        $this->assertFalse($event->isPermanent);
    }

    // =========================================================================
    // VendorReactivatedEvent Tests
    // =========================================================================

    #[Test]
    public function it_creates_suspension_expired_reactivation(): void
    {
        $event = VendorReactivatedEvent::suspensionExpired(
            vendorId: 'VND-REACT',
            reactivatedBy: 'SYSTEM',
            originalSuspensionReason: 'payment_issues',
        );

        $this->assertSame('VND-REACT', $event->vendorId);
        $this->assertSame('suspension_expired', $event->reactivationReason);
    }

    #[Test]
    public function it_creates_compliance_remediated_reactivation(): void
    {
        $event = VendorReactivatedEvent::complianceRemediated(
            vendorId: 'VND-COMP-OK',
            reactivatedBy: 'USR-COMPLIANCE',
            remediationDetails: ['action' => 'Renewed ISO 9001 certification'],
            verifiedBy: 'USR-AUDITOR',
        );

        $this->assertSame('compliance_remediated', $event->reactivationReason);
        $this->assertSame('USR-AUDITOR', $event->verifiedBy);
    }

    #[Test]
    public function it_creates_quality_improved_reactivation(): void
    {
        $event = VendorReactivatedEvent::qualityImproved(
            vendorId: 'VND-QUAL-OK',
            reactivatedBy: 'USR-QC',
            previousScore: 45.0,
            newScore: 82.0,
        );

        $this->assertSame('quality_improved', $event->reactivationReason);
    }

    #[Test]
    public function it_creates_administrative_reactivation(): void
    {
        $event = VendorReactivatedEvent::administrative(
            vendorId: 'VND-ADMIN-OK',
            reactivatedBy: 'USR-MGR',
            notes: 'Contract signed and payment received',
        );

        $this->assertSame('administrative', $event->reactivationReason);
    }

    #[Test]
    public function it_serializes_events_to_array(): void
    {
        $suspendedEvent = VendorSuspendedEvent::administrative(
            vendorId: 'VND-TEST',
            suspendedBy: 'USR-TEST',
            reason: 'Test',
        );

        $reactivatedEvent = VendorReactivatedEvent::administrative(
            vendorId: 'VND-TEST',
            reactivatedBy: 'USR-TEST',
            notes: 'Test reactivation',
        );

        $suspendedArray = $suspendedEvent->toArray();
        $reactivatedArray = $reactivatedEvent->toArray();

        $this->assertSame('vendor.suspended', $suspendedArray['event_type']);
        $this->assertSame('vendor.reactivated', $reactivatedArray['event_type']);
    }
}
