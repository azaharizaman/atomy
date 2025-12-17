<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts;

use Nexus\KycVerification\Enums\ReviewTrigger;
use Nexus\KycVerification\ValueObjects\ReviewSchedule;

/**
 * Interface for review scheduling operations.
 */
interface ReviewSchedulerInterface
{
    /**
     * Schedule a review for a party
     */
    public function scheduleReview(
        string $partyId,
        ReviewTrigger $trigger,
        ?\DateTimeImmutable $scheduledDate = null
    ): ReviewSchedule;

    /**
     * Get scheduled reviews for a party
     * 
     * @return array<ReviewSchedule>
     */
    public function getScheduledReviews(string $partyId): array;

    /**
     * Get next review for a party
     */
    public function getNextReview(string $partyId): ?ReviewSchedule;

    /**
     * Start a review
     */
    public function startReview(
        string $partyId,
        string $reviewId,
        ?string $reviewerId = null
    ): ReviewSchedule;

    /**
     * Complete a review
     */
    public function completeReview(
        string $partyId,
        string $reviewId,
        string $outcome,
        string $completedBy
    ): ReviewSchedule;

    /**
     * Cancel a scheduled review
     */
    public function cancelReview(
        string $partyId,
        string $reviewId,
        string $reason
    ): void;

    /**
     * Get overdue reviews
     * 
     * @return array<ReviewSchedule>
     */
    public function getOverdueReviews(): array;

    /**
     * Get reviews due within period
     * 
     * @return array<ReviewSchedule>
     */
    public function getReviewsDueSoon(int $withinDays = 7): array;

    /**
     * Get reviews by trigger type
     * 
     * @return array<ReviewSchedule>
     */
    public function getReviewsByTrigger(ReviewTrigger $trigger): array;

    /**
     * Get reviews assigned to reviewer
     * 
     * @return array<ReviewSchedule>
     */
    public function getReviewsByReviewer(string $reviewerId): array;

    /**
     * Calculate next review date based on risk level and policies
     */
    public function calculateNextReviewDate(string $partyId): \DateTimeImmutable;

    /**
     * Auto-schedule periodic review based on risk level
     */
    public function autoSchedulePeriodicReview(string $partyId): ReviewSchedule;

    /**
     * Reschedule a review
     */
    public function rescheduleReview(
        string $partyId,
        string $reviewId,
        \DateTimeImmutable $newDate,
        string $reason
    ): ReviewSchedule;

    /**
     * Get review statistics
     * 
     * @return array{total: int, overdue: int, in_progress: int, completed_this_month: int}
     */
    public function getReviewStatistics(): array;
}
