<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Services;

use Nexus\KycVerification\Contracts\KycProfilePersistInterface;
use Nexus\KycVerification\Contracts\KycProfileQueryInterface;
use Nexus\KycVerification\Contracts\Providers\AuditLoggerProviderInterface;
use Nexus\KycVerification\Contracts\ReviewSchedulerInterface;
use Nexus\KycVerification\Contracts\RiskAssessorInterface;
use Nexus\KycVerification\Enums\ReviewTrigger;
use Nexus\KycVerification\Enums\RiskLevel;
use Nexus\KycVerification\Exceptions\KycVerificationException;
use Nexus\KycVerification\Exceptions\ReviewOverdueException;
use Nexus\KycVerification\ValueObjects\ReviewSchedule;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Review Scheduler - manages KYC review scheduling and tracking.
 */
final readonly class ReviewScheduler implements ReviewSchedulerInterface
{
    /**
     * Review frequency in months based on risk level.
     */
    private const array REVIEW_FREQUENCY_MONTHS = [
        'low' => 24,
        'medium' => 12,
        'high' => 6,
        'very_high' => 3,
        'prohibited' => 1,
    ];

    public function __construct(
        private KycProfileQueryInterface $profileQuery,
        private KycProfilePersistInterface $profilePersist,
        private RiskAssessorInterface $riskAssessor,
        private AuditLoggerProviderInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function scheduleReview(
        string $partyId,
        ReviewTrigger $trigger,
        ?\DateTimeImmutable $scheduledDate = null
    ): ReviewSchedule {
        $profile = $this->profileQuery->findByPartyId($partyId);
        if ($profile === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'KYC profile not found'
            );
        }

        // Calculate scheduled date if not provided
        if ($scheduledDate === null) {
            $scheduledDate = $this->calculateScheduledDate($trigger);
        }

        $schedule = ReviewSchedule::schedule(
            partyId: $partyId,
            trigger: $trigger,
            scheduledDate: $scheduledDate,
        );

        $this->profilePersist->saveReviewSchedule($schedule);

        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            action: 'review_scheduled',
            description: 'KYC review scheduled',
            metadata: [
                'trigger' => $trigger->value,
                'scheduled_date' => $scheduledDate->format(\DateTimeInterface::ATOM),
                'due_date' => $schedule->dueDate->format(\DateTimeInterface::ATOM),
            ],
            performedBy: null
        );

        $this->logger->info('Review scheduled', [
            'party_id' => $partyId,
            'trigger' => $trigger->value,
            'scheduled_date' => $scheduledDate->format('Y-m-d'),
        ]);

        return $schedule;
    }

    public function getScheduledReviews(string $partyId): array
    {
        $profile = $this->profileQuery->findByPartyId($partyId);
        if ($profile === null) {
            return [];
        }

        // Get all reviews from profile metadata
        // In a real implementation, this would query a separate reviews table
        return $profile->metadata['scheduled_reviews'] ?? [];
    }

    public function getNextReview(string $partyId): ?ReviewSchedule
    {
        $reviews = $this->getScheduledReviews($partyId);
        
        // Filter to pending reviews and sort by scheduled date
        $pendingReviews = array_filter(
            $reviews,
            fn(ReviewSchedule $r) => !$r->isCompleted() && !$r->isCancelled()
        );

        if (empty($pendingReviews)) {
            return null;
        }

        usort(
            $pendingReviews,
            fn(ReviewSchedule $a, ReviewSchedule $b) => 
                $a->scheduledDate <=> $b->scheduledDate
        );

        return $pendingReviews[0] ?? null;
    }

    public function startReview(
        string $partyId,
        string $reviewId,
        ?string $reviewerId = null
    ): ReviewSchedule {
        $schedule = $this->findReview($partyId, $reviewId);
        if ($schedule === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'Review not found: ' . $reviewId
            );
        }

        if ($schedule->isCompleted()) {
            throw KycVerificationException::forParty(
                $partyId,
                'Review already completed'
            );
        }

        $updatedSchedule = $schedule->start($reviewerId);

        $this->profilePersist->saveReviewSchedule($updatedSchedule);

        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            action: 'review_started',
            description: 'KYC review started',
            metadata: [
                'reviewer_id' => $reviewerId,
                'started_at' => $updatedSchedule->startedAt?->format(\DateTimeInterface::ATOM),
            ],
            performedBy: $reviewerId
        );

        return $updatedSchedule;
    }

    public function completeReview(
        string $partyId,
        string $reviewId,
        string $outcome,
        string $completedBy
    ): ReviewSchedule {
        $schedule = $this->findReview($partyId, $reviewId);
        if ($schedule === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'Review not found: ' . $reviewId
            );
        }

        $updatedSchedule = $schedule->complete($outcome, $completedBy);

        $this->profilePersist->saveReviewSchedule($updatedSchedule);

        // Auto-schedule next periodic review
        if ($schedule->trigger === ReviewTrigger::SCHEDULED ||
            $schedule->trigger === ReviewTrigger::ONBOARDING) {
            $this->autoSchedulePeriodicReview($partyId);
        }

        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            action: 'review_completed',
            description: 'KYC review completed',
            metadata: [
                'outcome' => $outcome,
                'completed_by' => $completedBy,
                'was_overdue' => $schedule->isOverdue(),
            ],
            performedBy: $completedBy
        );

        $this->logger->info('Review completed', [
            'party_id' => $partyId,
            'outcome' => $outcome,
        ]);

        return $updatedSchedule;
    }

    public function cancelReview(
        string $partyId,
        string $reviewId,
        string $reason
    ): void {
        $schedule = $this->findReview($partyId, $reviewId);
        if ($schedule === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'Review not found: ' . $reviewId
            );
        }

        if ($schedule->isCompleted()) {
            throw KycVerificationException::forParty(
                $partyId,
                'Cannot cancel completed review'
            );
        }

        $this->profilePersist->deleteReviewSchedule($partyId, $reviewId);

        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            action: 'review_cancelled',
            description: 'KYC review cancelled',
            metadata: [
                'reason' => $reason,
            ],
            performedBy: null
        );

        $this->logger->info('Review cancelled', [
            'party_id' => $partyId,
            'reason' => $reason,
        ]);
    }

    public function getOverdueReviews(): array
    {
        $allProfiles = $this->profileQuery->findNeedingReview();
        $overdueReviews = [];

        foreach ($allProfiles as $profile) {
            $reviews = $this->getScheduledReviews($profile->partyId);
            foreach ($reviews as $review) {
                if ($review->isOverdue() && !$review->isCompleted()) {
                    $overdueReviews[] = $review;
                }
            }
        }

        return $overdueReviews;
    }

    public function getReviewsDueSoon(int $withinDays = 7): array
    {
        $allProfiles = $this->profileQuery->search([]);
        $dueReviews = [];
        $deadline = (new \DateTimeImmutable())->modify("+{$withinDays} days");

        foreach ($allProfiles as $profile) {
            $reviews = $this->getScheduledReviews($profile->partyId);
            foreach ($reviews as $review) {
                if (!$review->isCompleted() && 
                    !$review->isCancelled() &&
                    $review->scheduledDate <= $deadline) {
                    $dueReviews[] = $review;
                }
            }
        }

        // Sort by scheduled date
        usort(
            $dueReviews,
            fn(ReviewSchedule $a, ReviewSchedule $b) => 
                $a->scheduledDate <=> $b->scheduledDate
        );

        return $dueReviews;
    }

    public function getReviewsByTrigger(ReviewTrigger $trigger): array
    {
        $allProfiles = $this->profileQuery->search([]);
        $matchingReviews = [];

        foreach ($allProfiles as $profile) {
            $reviews = $this->getScheduledReviews($profile->partyId);
            foreach ($reviews as $review) {
                if ($review->trigger === $trigger) {
                    $matchingReviews[] = $review;
                }
            }
        }

        return $matchingReviews;
    }

    public function getReviewsByReviewer(string $reviewerId): array
    {
        $allProfiles = $this->profileQuery->search([]);
        $matchingReviews = [];

        foreach ($allProfiles as $profile) {
            $reviews = $this->getScheduledReviews($profile->partyId);
            foreach ($reviews as $review) {
                if ($review->reviewerId === $reviewerId && !$review->isCompleted()) {
                    $matchingReviews[] = $review;
                }
            }
        }

        return $matchingReviews;
    }

    public function calculateNextReviewDate(string $partyId): \DateTimeImmutable
    {
        $riskLevel = $this->riskAssessor->getRiskLevel($partyId);
        
        $months = self::REVIEW_FREQUENCY_MONTHS[$riskLevel?->value ?? 'medium'];
        
        return (new \DateTimeImmutable())->modify("+{$months} months");
    }

    public function autoSchedulePeriodicReview(string $partyId): ReviewSchedule
    {
        $nextReviewDate = $this->calculateNextReviewDate($partyId);

        return $this->scheduleReview(
            $partyId,
            ReviewTrigger::SCHEDULED,
            $nextReviewDate
        );
    }

    public function rescheduleReview(
        string $partyId,
        string $reviewId,
        \DateTimeImmutable $newDate,
        string $reason
    ): ReviewSchedule {
        $schedule = $this->findReview($partyId, $reviewId);
        if ($schedule === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'Review not found: ' . $reviewId
            );
        }

        if ($schedule->isCompleted()) {
            throw KycVerificationException::forParty(
                $partyId,
                'Cannot reschedule completed review'
            );
        }

        // Calculate new SLA deadline
        $slaHours = $schedule->trigger->slaHours();
        $newSlaDeadline = $newDate->modify("+{$slaHours} hours");

        $updatedSchedule = new ReviewSchedule(
            id: $schedule->id,
            partyId: $schedule->partyId,
            trigger: $schedule->trigger,
            scheduledDate: $newDate,
            slaDeadline: $newSlaDeadline,
            startedAt: $schedule->startedAt,
            completedAt: null,
            outcome: null,
            reviewerId: $schedule->reviewerId,
            completedBy: null,
            metadata: array_merge($schedule->metadata, [
                'rescheduled' => true,
                'reschedule_reason' => $reason,
                'original_date' => $schedule->scheduledDate->format(\DateTimeInterface::ATOM),
            ])
        );

        $this->profilePersist->saveReviewSchedule($updatedSchedule);

        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            action: 'review_rescheduled',
            description: 'KYC review rescheduled',
            metadata: [
                'original_date' => $schedule->scheduledDate->format(\DateTimeInterface::ATOM),
                'new_date' => $newDate->format(\DateTimeInterface::ATOM),
                'reason' => $reason,
            ],
            performedBy: null
        );

        return $updatedSchedule;
    }

    public function getReviewStatistics(): array
    {
        $allProfiles = $this->profileQuery->search([]);
        
        $stats = [
            'total' => 0,
            'overdue' => 0,
            'in_progress' => 0,
            'completed_this_month' => 0,
        ];

        $startOfMonth = new \DateTimeImmutable('first day of this month midnight');
        $endOfMonth = new \DateTimeImmutable('last day of this month 23:59:59');

        foreach ($allProfiles as $profile) {
            $reviews = $this->getScheduledReviews($profile->partyId);
            
            foreach ($reviews as $review) {
                $stats['total']++;

                if ($review->isOverdue() && !$review->isCompleted()) {
                    $stats['overdue']++;
                }

                if ($review->startedAt !== null && !$review->isCompleted()) {
                    $stats['in_progress']++;
                }

                if ($review->completedAt !== null &&
                    $review->completedAt >= $startOfMonth &&
                    $review->completedAt <= $endOfMonth) {
                    $stats['completed_this_month']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Find a specific review by ID.
     */
    private function findReview(string $partyId, string $reviewId): ?ReviewSchedule
    {
        $reviews = $this->getScheduledReviews($partyId);
        
        foreach ($reviews as $review) {
            if ($review->id === $reviewId) {
                return $review;
            }
        }

        return null;
    }

    /**
     * Calculate scheduled date based on trigger.
     */
    private function calculateScheduledDate(ReviewTrigger $trigger): \DateTimeImmutable
    {
        return match ($trigger) {
            ReviewTrigger::ONBOARDING => new \DateTimeImmutable(),
            ReviewTrigger::TRANSACTION_THRESHOLD => new \DateTimeImmutable(),
            ReviewTrigger::RISK_CHANGE => new \DateTimeImmutable(),
            ReviewTrigger::ADVERSE_MEDIA => new \DateTimeImmutable(),
            ReviewTrigger::SANCTIONS_UPDATE => new \DateTimeImmutable(),
            ReviewTrigger::NEW_JURISDICTION => new \DateTimeImmutable(),
            ReviewTrigger::MANUAL_REQUEST => (new \DateTimeImmutable())->modify('+1 day'),
            ReviewTrigger::REGULATORY => (new \DateTimeImmutable())->modify('+7 days'),
            ReviewTrigger::SCHEDULED => (new \DateTimeImmutable())->modify('+12 months'),
            ReviewTrigger::DOCUMENT_EXPIRED => (new \DateTimeImmutable())->modify('+30 days'),
            ReviewTrigger::UBO_CHANGE => new \DateTimeImmutable(),
            ReviewTrigger::INFORMATION_CHANGE => (new \DateTimeImmutable())->modify('+7 days'),
            ReviewTrigger::SUSPICIOUS_ACTIVITY => new \DateTimeImmutable(),
        };
    }

    /**
     * Generate unique review ID.
     */
    private function generateReviewId(): string
    {
        return 'REV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }
}
