<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\ValueObjects;

use Nexus\AmlCompliance\Enums\SarStatus;
use Nexus\AmlCompliance\ValueObjects\SuspiciousActivityReport;
use Nexus\AmlCompliance\ValueObjects\TransactionAlert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuspiciousActivityReport::class)]
final class SuspiciousActivityReportTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $activityStart = new \DateTimeImmutable('-30 days');
        $activityEnd = new \DateTimeImmutable();
        $createdAt = new \DateTimeImmutable();

        $sar = new SuspiciousActivityReport(
            sarId: 'SAR-2024-001',
            partyId: 'party-123',
            status: SarStatus::DRAFT,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test narrative',
            totalAmount: 50000.00,
            currency: 'USD',
            activityStartDate: $activityStart,
            activityEndDate: $activityEnd,
            transactionIds: ['txn-1', 'txn-2'],
            suspiciousActivities: ['Structuring'],
            alerts: [],
            filingReference: null,
            assignedOfficer: 'officer-123',
            createdBy: 'user-456',
            createdAt: $createdAt,
            subjectInfo: ['name' => 'Test Subject'],
            metadata: ['key' => 'value'],
        );

        $this->assertSame('SAR-2024-001', $sar->sarId);
        $this->assertSame('party-123', $sar->partyId);
        $this->assertSame(SarStatus::DRAFT, $sar->status);
        $this->assertSame(SuspiciousActivityReport::TYPE_STRUCTURING, $sar->type);
        $this->assertSame('Test narrative', $sar->narrative);
        $this->assertSame(50000.00, $sar->totalAmount);
        $this->assertSame('USD', $sar->currency);
        $this->assertSame(['txn-1', 'txn-2'], $sar->transactionIds);
    }

    public function test_constructor_validates_activity_dates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Activity end date cannot be before start date');

        new SuspiciousActivityReport(
            sarId: 'SAR-2024-001',
            partyId: 'party-123',
            status: SarStatus::DRAFT,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 1000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('2024-02-01'),
            activityEndDate: new \DateTimeImmutable('2024-01-01'), // Before start
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            createdBy: 'user-123',
            createdAt: new \DateTimeImmutable(),
            subjectInfo: [],
        );
    }

    public function test_generate_sar_id_creates_unique_id(): void
    {
        $id1 = SuspiciousActivityReport::generateSarId();
        $id2 = SuspiciousActivityReport::generateSarId();

        $this->assertStringStartsWith('SAR-', $id1);
        $this->assertNotSame($id1, $id2);
    }

    public function test_create_draft_factory(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_MONEY_LAUNDERING,
            narrative: 'Suspected money laundering activity',
            totalAmount: 100000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-30 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: ['txn-1', 'txn-2', 'txn-3'],
            suspiciousActivities: ['Layering', 'Round amounts'],
            alerts: [],
            subjectInfo: ['name' => 'John Doe'],
            createdBy: 'compliance-officer',
        );

        $this->assertStringStartsWith('SAR-', $sar->sarId);
        $this->assertSame('party-123', $sar->partyId);
        $this->assertSame(SarStatus::DRAFT, $sar->status);
        $this->assertSame(SuspiciousActivityReport::TYPE_MONEY_LAUNDERING, $sar->type);
    }

    public function test_from_array_creates_instance(): void
    {
        $data = [
            'sar_id' => 'SAR-2024-001',
            'party_id' => 'party-123',
            'status' => 'draft',
            'type' => 'structuring',
            'narrative' => 'Test narrative',
            'total_amount' => 50000.00,
            'currency' => 'USD',
            'activity_start_date' => '2024-01-01T00:00:00+00:00',
            'activity_end_date' => '2024-01-31T23:59:59+00:00',
            'transaction_ids' => ['txn-1'],
            'suspicious_activities' => ['Structuring'],
            'alerts' => [],
            'created_by' => 'user-123',
            'created_at' => '2024-02-01T10:00:00+00:00',
            'subject_info' => ['name' => 'Test'],
        ];

        $sar = SuspiciousActivityReport::fromArray($data);

        $this->assertSame('SAR-2024-001', $sar->sarId);
        $this->assertSame(SarStatus::DRAFT, $sar->status);
    }

    public function test_transition_to_changes_status(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $updated = $sar->transitionTo(SarStatus::PENDING_REVIEW);

        $this->assertSame(SarStatus::PENDING_REVIEW, $updated->status);
        // Original should be unchanged (immutable)
        $this->assertSame(SarStatus::DRAFT, $sar->status);
    }

    public function test_transition_to_throws_for_invalid_transition(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $this->expectException(\InvalidArgumentException::class);

        // Cannot go directly from DRAFT to SUBMITTED
        $sar->transitionTo(SarStatus::SUBMITTED);
    }

    public function test_is_editable(): void
    {
        $draft = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $this->assertTrue($draft->isEditable());
    }

    public function test_is_submitted(): void
    {
        $sar = new SuspiciousActivityReport(
            sarId: 'SAR-2024-001',
            partyId: 'party-123',
            status: SarStatus::SUBMITTED,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            createdBy: 'user-123',
            createdAt: new \DateTimeImmutable(),
            subjectInfo: [],
        );

        $this->assertTrue($sar->isSubmitted());
    }

    public function test_is_closed(): void
    {
        $sar = new SuspiciousActivityReport(
            sarId: 'SAR-2024-001',
            partyId: 'party-123',
            status: SarStatus::CLOSED,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            createdBy: 'user-123',
            createdAt: new \DateTimeImmutable(),
            subjectInfo: [],
            closedAt: new \DateTimeImmutable(),
            closureReason: 'Investigation complete',
        );

        $this->assertTrue($sar->isClosed());
    }

    public function test_get_activity_duration_days(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('2024-01-01'),
            activityEndDate: new \DateTimeImmutable('2024-01-31'),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $this->assertSame(30, $sar->getActivityDurationDays());
    }

    public function test_to_array_returns_structured_data(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test narrative',
            totalAmount: 50000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-30 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: ['txn-1'],
            suspiciousActivities: ['Structuring'],
            alerts: [],
            subjectInfo: ['name' => 'Test'],
            createdBy: 'user-123',
        );

        $array = $sar->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('sar_id', $array);
        $this->assertArrayHasKey('party_id', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('narrative', $array);
        $this->assertArrayHasKey('total_amount', $array);
        $this->assertArrayHasKey('currency', $array);
    }

    public function test_type_constants_exist(): void
    {
        $this->assertSame('structuring', SuspiciousActivityReport::TYPE_STRUCTURING);
        $this->assertSame('money_laundering', SuspiciousActivityReport::TYPE_MONEY_LAUNDERING);
        $this->assertSame('terrorist_financing', SuspiciousActivityReport::TYPE_TERRORIST_FINANCING);
        $this->assertSame('fraud', SuspiciousActivityReport::TYPE_FRAUD);
        $this->assertSame('identity_theft', SuspiciousActivityReport::TYPE_IDENTITY_THEFT);
        $this->assertSame('sanctions_evasion', SuspiciousActivityReport::TYPE_SANCTIONS_EVASION);
        $this->assertSame('bribery_corruption', SuspiciousActivityReport::TYPE_BRIBERY_CORRUPTION);
        $this->assertSame('tax_evasion', SuspiciousActivityReport::TYPE_TAX_EVASION);
        $this->assertSame('insider_trading', SuspiciousActivityReport::TYPE_INSIDER_TRADING);
        $this->assertSame('other', SuspiciousActivityReport::TYPE_OTHER);
    }

    public function test_get_all_types_returns_all_type_constants(): void
    {
        $types = SuspiciousActivityReport::getAllTypes();

        $this->assertCount(11, $types);
        $this->assertContains(SuspiciousActivityReport::TYPE_STRUCTURING, $types);
        $this->assertContains(SuspiciousActivityReport::TYPE_MONEY_LAUNDERING, $types);
        $this->assertContains(SuspiciousActivityReport::TYPE_TERRORIST_FINANCING, $types);
        $this->assertContains(SuspiciousActivityReport::TYPE_SUSPICIOUS_PARTY, $types);
    }

    public function test_submit_for_review_transitions_to_pending_review(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $submitted = $sar->submitForReview();

        $this->assertSame(SarStatus::PENDING_REVIEW, $submitted->status);
        $this->assertSame(SarStatus::DRAFT, $sar->status);
    }

    public function test_approve_transitions_to_approved(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $pendingReview = $sar->submitForReview();
        $approved = $pendingReview->approve();

        $this->assertSame(SarStatus::APPROVED, $approved->status);
    }

    public function test_submit_to_authority_transitions_and_sets_filing_reference(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $approved = $sar->submitForReview()->approve();
        $submitted = $approved->submitToAuthority('FINCEN-2024-123456');

        $this->assertSame(SarStatus::SUBMITTED, $submitted->status);
        $this->assertSame('FINCEN-2024-123456', $submitted->filingReference);
        $this->assertNotNull($submitted->submittedAt);
    }

    public function test_close_transitions_and_sets_closure_reason(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $submitted = $sar->submitForReview()->approve()->submitToAuthority('REF-123');
        $closed = $submitted->close('Investigation complete, case resolved');

        $this->assertSame(SarStatus::CLOSED, $closed->status);
        $this->assertSame('Investigation complete, case resolved', $closed->closureReason);
        $this->assertNotNull($closed->closedAt);
    }

    public function test_assign_officer_sets_assigned_officer(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $assigned = $sar->assignOfficer('officer-456');

        $this->assertSame('officer-456', $assigned->assignedOfficer);
        $this->assertNull($sar->assignedOfficer);
    }

    public function test_is_overdue_false_within_30_days(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $this->assertFalse($sar->isOverdue());
    }

    public function test_is_overdue_true_after_30_days(): void
    {
        $oldSar = new SuspiciousActivityReport(
            sarId: 'SAR-OLD-001',
            partyId: 'party-123',
            status: SarStatus::DRAFT,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-60 days'),
            activityEndDate: new \DateTimeImmutable('-31 days'),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            createdBy: 'user-123',
            createdAt: new \DateTimeImmutable('-35 days'),
            subjectInfo: [],
        );

        $this->assertTrue($oldSar->isOverdue());
    }

    public function test_is_overdue_false_when_submitted(): void
    {
        $submittedSar = new SuspiciousActivityReport(
            sarId: 'SAR-2024-001',
            partyId: 'party-123',
            status: SarStatus::SUBMITTED,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-60 days'),
            activityEndDate: new \DateTimeImmutable('-31 days'),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            createdBy: 'user-123',
            createdAt: new \DateTimeImmutable('-35 days'),
            subjectInfo: [],
        );

        $this->assertFalse($submittedSar->isOverdue());
    }

    public function test_get_days_until_deadline_positive_within_30_days(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $daysRemaining = $sar->getDaysUntilDeadline();

        $this->assertGreaterThanOrEqual(29, $daysRemaining);
        $this->assertLessThanOrEqual(30, $daysRemaining);
    }

    public function test_get_days_until_deadline_negative_after_30_days(): void
    {
        $oldSar = new SuspiciousActivityReport(
            sarId: 'SAR-OLD-001',
            partyId: 'party-123',
            status: SarStatus::DRAFT,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-60 days'),
            activityEndDate: new \DateTimeImmutable('-31 days'),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            createdBy: 'user-123',
            createdAt: new \DateTimeImmutable('-35 days'),
            subjectInfo: [],
        );

        $daysRemaining = $oldSar->getDaysUntilDeadline();

        $this->assertLessThan(0, $daysRemaining);
    }

    public function test_is_pending_returns_true_for_pending_statuses(): void
    {
        $pending = new SuspiciousActivityReport(
            sarId: 'SAR-2024-001',
            partyId: 'party-123',
            status: SarStatus::PENDING_REVIEW,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            createdBy: 'user-123',
            createdAt: new \DateTimeImmutable(),
            subjectInfo: [],
        );

        $this->assertTrue($pending->isPending());
    }

    public function test_get_type_label_returns_human_readable_label(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_MONEY_LAUNDERING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $this->assertSame('Money Laundering', $sar->getTypeLabel());
    }

    public function test_get_type_label_for_all_types(): void
    {
        $types = [
            SuspiciousActivityReport::TYPE_STRUCTURING => 'Structuring/Smurfing',
            SuspiciousActivityReport::TYPE_MONEY_LAUNDERING => 'Money Laundering',
            SuspiciousActivityReport::TYPE_TERRORIST_FINANCING => 'Terrorist Financing',
            SuspiciousActivityReport::TYPE_FRAUD => 'Fraud',
            SuspiciousActivityReport::TYPE_IDENTITY_THEFT => 'Identity Theft',
            SuspiciousActivityReport::TYPE_SANCTIONS_EVASION => 'Sanctions Evasion',
            SuspiciousActivityReport::TYPE_BRIBERY_CORRUPTION => 'Bribery/Corruption',
            SuspiciousActivityReport::TYPE_TAX_EVASION => 'Tax Evasion',
            SuspiciousActivityReport::TYPE_INSIDER_TRADING => 'Insider Trading',
            SuspiciousActivityReport::TYPE_OTHER => 'Other Suspicious Activity',
        ];

        foreach ($types as $type => $expectedLabel) {
            $sar = new SuspiciousActivityReport(
                sarId: 'SAR-TEST',
                partyId: 'party-123',
                status: SarStatus::DRAFT,
                type: $type,
                narrative: 'Test',
                totalAmount: 1000.00,
                currency: 'USD',
                activityStartDate: new \DateTimeImmutable('-7 days'),
                activityEndDate: new \DateTimeImmutable(),
                transactionIds: [],
                suspiciousActivities: [],
                alerts: [],
                createdBy: 'system',
                subjectInfo: [],
            );

            $this->assertSame($expectedLabel, $sar->getTypeLabel(), "Type {$type} should have label {$expectedLabel}");
        }
    }

    public function test_get_activity_period_days(): void
    {
        $sar = new SuspiciousActivityReport(
            sarId: 'SAR-2024-001',
            partyId: 'party-123',
            status: SarStatus::DRAFT,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('2024-01-01'),
            activityEndDate: new \DateTimeImmutable('2024-01-15'),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            createdBy: 'user-123',
            createdAt: new \DateTimeImmutable(),
            subjectInfo: [],
        );

        $this->assertSame(14, $sar->getActivityPeriodDays());
    }

    public function test_with_narrative_creates_new_instance_with_updated_narrative(): void
    {
        $original = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Original narrative',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $updated = $original->withNarrative('Updated narrative with more details');

        $this->assertSame('Original narrative', $original->narrative);
        $this->assertSame('Updated narrative with more details', $updated->narrative);
    }

    public function test_with_updated_by_sets_metadata(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $updated = $sar->withUpdatedBy('user-456');

        $this->assertSame('user-456', $updated->metadata['updated_by']);
        $this->assertArrayHasKey('updated_at', $updated->metadata);
    }

    public function test_with_assigned_officer_delegates_to_assign_officer(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $assigned = $sar->withAssignedOfficer('officer-789');

        $this->assertSame('officer-789', $assigned->assignedOfficer);
    }

    public function test_with_rejection_reason_sets_metadata(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $rejected = $sar->withRejectionReason('Insufficient evidence');

        $this->assertSame('Insufficient evidence', $rejected->metadata['rejection_reason']);
        $this->assertArrayHasKey('rejected_at', $rejected->metadata);
    }

    public function test_with_cancellation_reason_sets_metadata(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            subjectInfo: [],
            createdBy: 'user-123',
        );

        $cancelled = $sar->withCancellationReason('Duplicate SAR filed');

        $this->assertSame('Duplicate SAR filed', $cancelled->metadata['cancellation_reason']);
        $this->assertArrayHasKey('cancelled_at', $cancelled->metadata);
    }

    public function test_is_cancelled_returns_true_for_cancelled_status(): void
    {
        $cancelled = new SuspiciousActivityReport(
            sarId: 'SAR-2024-001',
            partyId: 'party-123',
            status: SarStatus::CANCELLED,
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test',
            totalAmount: 10000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-7 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: [],
            suspiciousActivities: [],
            alerts: [],
            createdBy: 'user-123',
            createdAt: new \DateTimeImmutable(),
            subjectInfo: [],
        );

        // isClosed() returns true for CANCELLED
        $this->assertTrue($cancelled->isClosed());
    }

    public function test_from_array_with_alert_data(): void
    {
        $data = [
            'sar_id' => 'SAR-2024-001',
            'party_id' => 'party-123',
            'status' => 'draft',
            'type' => 'structuring',
            'narrative' => 'Test narrative',
            'total_amount' => 50000.00,
            'currency' => 'USD',
            'activity_start_date' => '2024-01-01T00:00:00+00:00',
            'activity_end_date' => '2024-01-31T23:59:59+00:00',
            'transaction_ids' => ['txn-1'],
            'suspicious_activities' => ['Structuring'],
            'alerts' => [
                [
                    'alert_id' => 'ALERT-001',
                    'party_id' => 'party-123',
                    'type' => 'structuring',
                    'severity' => 'high',
                    'triggered_rules' => ['rule-1'],
                    'details' => [],
                    'is_reviewed' => false,
                    'is_dismissed' => false,
                    'reviewed_by' => null,
                    'review_notes' => null,
                    'created_at' => '2024-01-15T10:00:00+00:00',
                    'reviewed_at' => null,
                ],
            ],
            'created_by' => 'user-123',
            'created_at' => '2024-02-01T10:00:00+00:00',
            'subject_info' => ['name' => 'Test'],
        ];

        $sar = SuspiciousActivityReport::fromArray($data);

        $this->assertCount(1, $sar->alerts);
        $this->assertInstanceOf(TransactionAlert::class, $sar->alerts[0]);
    }

    public function test_to_array_includes_all_fields(): void
    {
        $sar = SuspiciousActivityReport::createDraft(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_STRUCTURING,
            narrative: 'Test narrative',
            totalAmount: 50000.00,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-30 days'),
            activityEndDate: new \DateTimeImmutable(),
            transactionIds: ['txn-1', 'txn-2'],
            suspiciousActivities: ['Structuring'],
            alerts: [],
            subjectInfo: ['name' => 'Test'],
            createdBy: 'user-123',
        );

        $array = $sar->toArray();

        // Verify all expected keys exist
        $expectedKeys = [
            'sar_id', 'party_id', 'status', 'status_description', 'is_editable',
            'is_pending', 'type', 'type_label', 'narrative', 'total_amount',
            'currency', 'activity_start_date', 'activity_end_date', 'activity_period_days',
            'transaction_ids', 'transaction_count', 'suspicious_activities', 'alerts',
            'alert_count', 'filing_reference', 'assigned_officer', 'created_by',
            'created_at', 'submitted_at', 'closed_at', 'closure_reason', 'is_overdue',
            'days_until_deadline', 'subject_info', 'metadata',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "Missing key: {$key}");
        }

        $this->assertSame(2, $array['transaction_count']);
    }
}
