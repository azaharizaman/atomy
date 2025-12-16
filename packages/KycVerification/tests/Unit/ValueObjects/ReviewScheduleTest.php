<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\KycVerification\Enums\ReviewTrigger;
use Nexus\KycVerification\ValueObjects\ReviewSchedule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReviewSchedule::class)]
final class ReviewScheduleTest extends TestCase
{
    #[Test]
    public function it_can_be_created_via_constructor(): void
    {
        $scheduledDate = new DateTimeImmutable();
        $dueDate = new DateTimeImmutable('+48 hours');

        $schedule = new ReviewSchedule(
            partyId: 'PARTY-001',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: $scheduledDate,
            dueDate: $dueDate,
            priority: 3,
            status: ReviewSchedule::STATUS_SCHEDULED,
            requiredActions: ['verify_identity', 'check_documents'],
            assignedReviewers: ['reviewer-001'],
            notes: 'Annual review',
        );

        $this->assertSame('PARTY-001', $schedule->partyId);
        $this->assertSame(ReviewTrigger::SCHEDULED, $schedule->trigger);
        $this->assertSame($scheduledDate, $schedule->scheduledDate);
        $this->assertSame($dueDate, $schedule->dueDate);
        $this->assertSame(3, $schedule->priority);
        $this->assertSame(ReviewSchedule::STATUS_SCHEDULED, $schedule->status);
        $this->assertSame(['verify_identity', 'check_documents'], $schedule->requiredActions);
        $this->assertSame(['reviewer-001'], $schedule->assignedReviewers);
        $this->assertSame('Annual review', $schedule->notes);
    }

    #[Test]
    public function schedule_factory_creates_with_trigger_defaults(): void
    {
        $schedule = ReviewSchedule::schedule(
            partyId: 'PARTY-002',
            trigger: ReviewTrigger::TRANSACTION_THRESHOLD,
        );

        $this->assertSame('PARTY-002', $schedule->partyId);
        $this->assertSame(ReviewTrigger::TRANSACTION_THRESHOLD, $schedule->trigger);
        $this->assertSame(ReviewTrigger::TRANSACTION_THRESHOLD->priority(), $schedule->priority);
        $this->assertSame(ReviewSchedule::STATUS_SCHEDULED, $schedule->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $schedule->scheduledDate);
        $this->assertInstanceOf(DateTimeImmutable::class, $schedule->dueDate);
    }

    #[Test]
    public function schedule_factory_uses_custom_date(): void
    {
        $customDate = new DateTimeImmutable('+7 days');

        $schedule = ReviewSchedule::schedule(
            partyId: 'PARTY-003',
            trigger: ReviewTrigger::ONBOARDING,
            scheduledDate: $customDate,
        );

        $this->assertSame($customDate, $schedule->scheduledDate);
        $this->assertSame(ReviewTrigger::ONBOARDING, $schedule->trigger);
    }

    #[Test]
    public function start_transitions_to_in_progress(): void
    {
        $original = ReviewSchedule::schedule(
            partyId: 'PARTY-004',
            trigger: ReviewTrigger::SCHEDULED,
        );

        $started = $original->start('reviewer-001');

        $this->assertSame(ReviewSchedule::STATUS_SCHEDULED, $original->status);
        $this->assertSame(ReviewSchedule::STATUS_IN_PROGRESS, $started->status);
        $this->assertNull($original->startedAt);
        $this->assertInstanceOf(DateTimeImmutable::class, $started->startedAt);
        $this->assertContains('reviewer-001', $started->assignedReviewers);
    }

    #[Test]
    public function complete_transitions_to_completed(): void
    {
        $started = ReviewSchedule::schedule(
            partyId: 'PARTY-005',
            trigger: ReviewTrigger::ADVERSE_MEDIA,
        )->start('reviewer-001');

        $completed = $started->complete(
            completedBy: 'reviewer-001',
            outcome: 'verified_no_issues',
        );

        $this->assertSame(ReviewSchedule::STATUS_COMPLETED, $completed->status);
        $this->assertSame('reviewer-001', $completed->completedBy);
        $this->assertSame('verified_no_issues', $completed->outcome);
        $this->assertInstanceOf(DateTimeImmutable::class, $completed->completedAt);
    }

    #[Test]
    public function is_overdue_returns_true_for_past_due_date(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-006',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable('-2 days'),
            dueDate: new DateTimeImmutable('-1 day'),
            priority: 3,
            status: ReviewSchedule::STATUS_SCHEDULED,
        );

        $this->assertTrue($schedule->isOverdue());
    }

    #[Test]
    public function is_overdue_returns_false_for_future_due_date(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-007',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable(),
            dueDate: new DateTimeImmutable('+1 day'),
            priority: 3,
            status: ReviewSchedule::STATUS_SCHEDULED,
        );

        $this->assertFalse($schedule->isOverdue());
    }

    #[Test]
    public function is_overdue_returns_false_when_completed(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-008',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable('-2 days'),
            dueDate: new DateTimeImmutable('-1 day'),
            priority: 3,
            status: ReviewSchedule::STATUS_COMPLETED,
        );

        $this->assertFalse($schedule->isOverdue());
    }

    #[Test]
    public function is_overdue_returns_false_when_cancelled(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-009',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable('-2 days'),
            dueDate: new DateTimeImmutable('-1 day'),
            priority: 3,
            status: ReviewSchedule::STATUS_CANCELLED,
        );

        $this->assertFalse($schedule->isOverdue());
    }

    #[Test]
    public function is_due_soon_checks_threshold(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-010',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable(),
            dueDate: new DateTimeImmutable('+3 days'),
            priority: 3,
            status: ReviewSchedule::STATUS_SCHEDULED,
        );

        $this->assertTrue($schedule->isDueSoon(7));
        $this->assertFalse($schedule->isDueSoon(2));
    }

    #[Test]
    public function is_due_soon_returns_false_when_completed(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-011',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable(),
            dueDate: new DateTimeImmutable('+1 day'),
            priority: 3,
            status: ReviewSchedule::STATUS_COMPLETED,
        );

        $this->assertFalse($schedule->isDueSoon(7));
    }

    #[Test]
    public function is_due_soon_returns_false_when_overdue(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-012',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable('-2 days'),
            dueDate: new DateTimeImmutable('-1 day'),
            priority: 3,
            status: ReviewSchedule::STATUS_SCHEDULED,
        );

        $this->assertFalse($schedule->isDueSoon(7));
    }

    #[Test]
    public function days_until_due_returns_positive_for_future(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-013',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable(),
            dueDate: new DateTimeImmutable('+10 days'),
            priority: 3,
            status: ReviewSchedule::STATUS_SCHEDULED,
        );

        $days = $schedule->daysUntilDue();
        $this->assertGreaterThanOrEqual(9, $days);
        $this->assertLessThanOrEqual(11, $days);
    }

    #[Test]
    public function days_until_due_returns_negative_for_past(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-014',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable('-10 days'),
            dueDate: new DateTimeImmutable('-5 days'),
            priority: 3,
            status: ReviewSchedule::STATUS_SCHEDULED,
        );

        $days = $schedule->daysUntilDue();
        $this->assertLessThanOrEqual(-4, $days);
        $this->assertGreaterThanOrEqual(-6, $days);
    }

    #[Test]
    public function is_in_progress_checks_status(): void
    {
        $scheduled = ReviewSchedule::schedule('PARTY-015', ReviewTrigger::SCHEDULED);
        $started = $scheduled->start();

        $this->assertFalse($scheduled->isInProgress());
        $this->assertTrue($started->isInProgress());
    }

    #[Test]
    public function is_completed_checks_status(): void
    {
        $scheduled = ReviewSchedule::schedule('PARTY-016', ReviewTrigger::SCHEDULED);
        $completed = $scheduled->start()->complete('reviewer-001', 'approved');

        $this->assertFalse($scheduled->isCompleted());
        $this->assertTrue($completed->isCompleted());
    }

    #[Test]
    public function is_urgent_checks_trigger_and_overdue(): void
    {
        // Sanctions update is always urgent
        $urgent = ReviewSchedule::schedule('PARTY-017', ReviewTrigger::SANCTIONS_UPDATE);
        $this->assertTrue($urgent->isUrgent());

        // Scheduled is not urgent by default
        $normal = ReviewSchedule::schedule('PARTY-018', ReviewTrigger::SCHEDULED);
        $this->assertFalse($normal->isUrgent());

        // Overdue makes it urgent
        $overdue = new ReviewSchedule(
            partyId: 'PARTY-019',
            trigger: ReviewTrigger::SCHEDULED,
            scheduledDate: new DateTimeImmutable('-2 days'),
            dueDate: new DateTimeImmutable('-1 day'),
            priority: 3,
            status: ReviewSchedule::STATUS_SCHEDULED,
        );
        $this->assertTrue($overdue->isUrgent());
    }

    #[Test]
    public function can_be_automated_delegates_to_trigger(): void
    {
        // Document expired can be automated
        $automate = ReviewSchedule::schedule('PARTY-020', ReviewTrigger::DOCUMENT_EXPIRED);
        $this->assertTrue($automate->canBeAutomated());

        // Sanctions update cannot be automated
        $manual = ReviewSchedule::schedule('PARTY-021', ReviewTrigger::SANCTIONS_UPDATE);
        $this->assertFalse($manual->canBeAutomated());
    }

    #[Test]
    public function get_sla_hours_delegates_to_trigger(): void
    {
        $schedule = ReviewSchedule::schedule('PARTY-022', ReviewTrigger::SANCTIONS_UPDATE);
        $this->assertSame(ReviewTrigger::SANCTIONS_UPDATE->slaHours(), $schedule->getSlaHours());
    }

    #[Test]
    public function get_sla_status_returns_complete_data(): void
    {
        $schedule = ReviewSchedule::schedule('PARTY-023', ReviewTrigger::SCHEDULED);

        $status = $schedule->getSlaStatus();

        $this->assertArrayHasKey('within_sla', $status);
        $this->assertArrayHasKey('hours_remaining', $status);
        $this->assertArrayHasKey('percentage_used', $status);
        $this->assertTrue($status['within_sla']);
        $this->assertIsInt($status['hours_remaining']);
        $this->assertIsNumeric($status['percentage_used']); // Can be int 0 or float
    }

    #[Test]
    public function to_array_returns_complete_data(): void
    {
        $schedule = new ReviewSchedule(
            partyId: 'PARTY-024',
            trigger: ReviewTrigger::RISK_CHANGE,
            scheduledDate: new DateTimeImmutable('2024-01-15 10:00:00'),
            dueDate: new DateTimeImmutable('2024-01-16 10:00:00'),
            priority: 2,
            status: ReviewSchedule::STATUS_SCHEDULED,
            requiredActions: ['check_risk'],
            assignedReviewers: ['reviewer-001'],
            notes: 'Risk level changed from LOW to HIGH',
        );

        $array = $schedule->toArray();

        $this->assertSame('PARTY-024', $array['party_id']);
        $this->assertSame('risk_change', $array['trigger']);
        $this->assertSame('Risk Level Change', $array['trigger_label']);
        $this->assertSame(2, $array['priority']);
        $this->assertSame(ReviewSchedule::STATUS_SCHEDULED, $array['status']);
        $this->assertSame(['check_risk'], $array['required_actions']);
        $this->assertSame(['reviewer-001'], $array['assigned_reviewers']);
        $this->assertSame('Risk level changed from LOW to HIGH', $array['notes']);
        $this->assertArrayHasKey('is_overdue', $array);
        $this->assertArrayHasKey('is_urgent', $array);
        $this->assertArrayHasKey('days_until_due', $array);
        $this->assertArrayHasKey('sla_status', $array);
    }

    #[Test]
    #[DataProvider('statusConstantsProvider')]
    public function status_constants_have_correct_values(string $constant, string $expected): void
    {
        $this->assertSame($expected, constant(ReviewSchedule::class . '::' . $constant));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function statusConstantsProvider(): array
    {
        return [
            'STATUS_SCHEDULED' => ['STATUS_SCHEDULED', 'scheduled'],
            'STATUS_IN_PROGRESS' => ['STATUS_IN_PROGRESS', 'in_progress'],
            'STATUS_COMPLETED' => ['STATUS_COMPLETED', 'completed'],
            'STATUS_CANCELLED' => ['STATUS_CANCELLED', 'cancelled'],
            'STATUS_OVERDUE' => ['STATUS_OVERDUE', 'overdue'],
        ];
    }
}
