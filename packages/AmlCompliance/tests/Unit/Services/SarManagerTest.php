<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Services;

use Nexus\AmlCompliance\Contracts\PartyInterface;
use Nexus\AmlCompliance\Enums\RiskLevel;
use Nexus\AmlCompliance\Enums\SarStatus;
use Nexus\AmlCompliance\Exceptions\SarGenerationFailedException;
use Nexus\AmlCompliance\Services\SarManager;
use Nexus\AmlCompliance\ValueObjects\AmlRiskScore;
use Nexus\AmlCompliance\ValueObjects\RiskFactors;
use Nexus\AmlCompliance\ValueObjects\SuspiciousActivityReport;
use Nexus\AmlCompliance\ValueObjects\TransactionAlert;
use Nexus\AmlCompliance\ValueObjects\TransactionMonitoringResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(SarManager::class)]
final class SarManagerTest extends TestCase
{
    private SarManager $sarManager;

    protected function setUp(): void
    {
        $this->sarManager = new SarManager(new NullLogger());
    }

    private function createPartyMock(
        string $id = 'party-123',
        string $name = 'Test Party'
    ): PartyInterface&MockObject {
        $party = $this->createMock(PartyInterface::class);
        $party->method('getId')->willReturn($id);
        $party->method('getName')->willReturn($name);
        $party->method('getCountryCode')->willReturn('US');

        return $party;
    }

    private function createMonitoringResult(
        bool $isSuspicious = true,
        int $riskScore = 75
    ): TransactionMonitoringResult {
        $alerts = $isSuspicious ? [
            TransactionAlert::structuring(9500.0, 5, 10000.0),
        ] : [];

        return new TransactionMonitoringResult(
            partyId: 'party-123',
            isSuspicious: $isSuspicious,
            riskScore: $riskScore,
            reasons: $isSuspicious ? ['Structuring detected'] : [],
            alerts: $alerts,
            patterns: $isSuspicious ? ['structuring'] : [],
            analyzedAt: new \DateTimeImmutable(),
            periodStart: new \DateTimeImmutable('-30 days'),
            periodEnd: new \DateTimeImmutable(),
            transactionCount: 10,
            totalVolume: 50000.0,
        );
    }

    private function createRiskScore(): AmlRiskScore
    {
        return new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 80,
            riskLevel: RiskLevel::HIGH,
            factors: new RiskFactors(80, 60, 90, 70),
            assessedAt: new \DateTimeImmutable(),
        );
    }

    public function test_create_from_monitoring_returns_sar(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();

        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $this->assertInstanceOf(SuspiciousActivityReport::class, $sar);
        $this->assertSame('party-123', $sar->partyId);
        $this->assertSame(SarStatus::DRAFT, $sar->status);
    }

    public function test_create_from_monitoring_throws_if_not_suspicious(): void
    {
        $result = $this->createMonitoringResult(isSuspicious: false, riskScore: 10);
        $party = $this->createPartyMock();

        $this->expectException(SarGenerationFailedException::class);

        $this->sarManager->createFromMonitoring($result, $party, 'user-123');
    }

    public function test_create_from_risk_assessment_returns_sar(): void
    {
        $riskScore = $this->createRiskScore();
        $party = $this->createPartyMock();

        $sar = $this->sarManager->createFromRiskAssessment(
            $riskScore,
            $party,
            'High risk party identified',
            'user-123'
        );

        $this->assertInstanceOf(SuspiciousActivityReport::class, $sar);
        $this->assertSame('party-123', $sar->partyId);
        $this->assertSame(SarStatus::DRAFT, $sar->status);
    }

    public function test_create_manual_returns_sar(): void
    {
        $sar = $this->sarManager->createManual(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_MONEY_LAUNDERING,
            narrative: 'Manual SAR for suspected money laundering activity',
            amount: 100000.0,
            currency: 'USD',
            activityStartDate: new \DateTimeImmutable('-30 days'),
            activityEndDate: new \DateTimeImmutable(),
            createdBy: 'user-123'
        );

        $this->assertInstanceOf(SuspiciousActivityReport::class, $sar);
        $this->assertSame('party-123', $sar->partyId);
        $this->assertSame(SuspiciousActivityReport::TYPE_MONEY_LAUNDERING, $sar->type);
        $this->assertSame(SarStatus::DRAFT, $sar->status);
    }

    public function test_create_manual_throws_for_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sarManager->createManual(
            partyId: 'party-123',
            type: 'invalid_type',
            narrative: 'Test narrative',
            amount: 1000.0,
            currency: 'USD',
            activityStartDate: null,
            activityEndDate: null,
            createdBy: 'user-123'
        );
    }

    public function test_update_narrative_modifies_sar(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $newNarrative = 'Updated narrative with more details about the suspicious activity.';
        $updated = $this->sarManager->updateNarrative($sar->sarId, $newNarrative, 'user-123');

        $this->assertSame($newNarrative, $updated->narrative);
    }

    public function test_submit_for_review_changes_status(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $submitted = $this->sarManager->submitForReview($sar->sarId, 'user-123');

        $this->assertSame(SarStatus::PENDING_REVIEW, $submitted->status);
    }

    public function test_approve_changes_status(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        // First submit for review
        $this->sarManager->submitForReview($sar->sarId, 'user-123');

        // Then approve
        $approved = $this->sarManager->approve($sar->sarId, 'approver-123');

        $this->assertSame(SarStatus::APPROVED, $approved->status);
    }

    public function test_reject_changes_status(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        // First submit for review
        $this->sarManager->submitForReview($sar->sarId, 'user-123');

        // Then reject - params are: sarId, rejectedBy, reason
        $rejected = $this->sarManager->reject($sar->sarId, 'reviewer-123', 'Insufficient evidence');

        $this->assertSame(SarStatus::REJECTED, $rejected->status);
    }

    public function test_submit_to_authority_requires_approval(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        // Try to submit without approval
        $this->expectException(SarGenerationFailedException::class);

        $this->sarManager->submitToAuthority($sar->sarId, 'FIL-2024-001', 'submitter-123');
    }

    public function test_submit_to_authority_works_after_approval(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        // Submit for review → approve → submit to authority
        $this->sarManager->submitForReview($sar->sarId, 'user-123');
        $this->sarManager->approve($sar->sarId, 'approver-123');
        $submitted = $this->sarManager->submitToAuthority($sar->sarId, 'FIL-2024-001', 'submitter-123');

        $this->assertSame(SarStatus::SUBMITTED, $submitted->status);
    }

    public function test_close_marks_sar_as_closed(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        // Go through workflow
        $this->sarManager->submitForReview($sar->sarId, 'user-123');
        $this->sarManager->approve($sar->sarId, 'approver-123');
        $this->sarManager->submitToAuthority($sar->sarId, 'FIL-2024-001', 'submitter-123');

        // Close
        $closed = $this->sarManager->close($sar->sarId, 'Investigation complete', 'closer-123');

        $this->assertSame(SarStatus::CLOSED, $closed->status);
    }

    public function test_cancel_marks_sar_as_cancelled(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $cancelled = $this->sarManager->cancel($sar->sarId, 'No longer required', 'user-123');

        $this->assertSame(SarStatus::CANCELLED, $cancelled->status);
    }

    public function test_assign_officer_updates_assignment(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $assigned = $this->sarManager->assignOfficer($sar->sarId, 'officer-456', 'admin-123');

        $this->assertSame('officer-456', $assigned->assignedOfficer);
    }

    public function test_validate_returns_errors_for_incomplete_sar(): void
    {
        // Create SAR with minimal data
        $sar = $this->sarManager->createManual(
            partyId: 'party-123',
            type: SuspiciousActivityReport::TYPE_OTHER,
            narrative: 'Short', // Too short
            amount: null,
            currency: null,
            activityStartDate: null,
            activityEndDate: null,
            createdBy: 'user-123'
        );

        $errors = $this->sarManager->validate($sar);

        $this->assertIsArray($errors);
        // Should have validation errors for short narrative
        $this->assertNotEmpty($errors);
    }

    public function test_is_overdue_returns_false_for_new_sar(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $isOverdue = $this->sarManager->isOverdue($sar);

        $this->assertFalse($isOverdue);
    }

    public function test_get_days_until_deadline_returns_positive(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $days = $this->sarManager->getDaysUntilDeadline($sar);

        $this->assertGreaterThan(0, $days);
        $this->assertLessThanOrEqual(30, $days); // FinCEN 30-day deadline
    }

    public function test_generate_summary_returns_structured_data(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $summary = $this->sarManager->generateSummary($sar);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('sar_id', $summary);
        $this->assertArrayHasKey('status', $summary);
        $this->assertArrayHasKey('party_id', $summary);
    }

    public function test_find_by_id_returns_sar(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $found = $this->sarManager->findById($sar->sarId);

        $this->assertSame($sar->sarId, $found->sarId);
    }

    public function test_find_by_id_throws_for_not_found(): void
    {
        $this->expectException(SarGenerationFailedException::class);

        $this->sarManager->findById('non-existent-id');
    }

    public function test_exists_returns_true_for_existing_sar(): void
    {
        $result = $this->createMonitoringResult();
        $party = $this->createPartyMock();
        $sar = $this->sarManager->createFromMonitoring($result, $party, 'user-123');

        $this->assertTrue($this->sarManager->exists($sar->sarId));
    }

    public function test_exists_returns_false_for_non_existing(): void
    {
        $this->assertFalse($this->sarManager->exists('non-existent-id'));
    }
}
