<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Contracts;

use Nexus\Sanctions\Enums\ScreeningFrequency;
use Nexus\Sanctions\ValueObjects\ScreeningResult;

/**
 * Interface for periodic/scheduled sanctions screening management.
 * 
 * Provides contract for managing ongoing screening schedules for parties.
 * Ensures parties are re-screened at appropriate intervals based on risk levels.
 * 
 * Periodic Screening Importance:
 * - Sanctions lists are updated frequently (OFAC updates daily)
 * - PEP status can change as officials take/leave positions
 * - Risk profiles change over time requiring frequency adjustments
 * - Regulatory requirement for ongoing monitoring (FATF Recommendation 12)
 * 
 * Implementing classes should provide:
 * - Scheduled re-screening with frequency management
 * - Batch processing for efficiency
 * - Next screening date calculation
 * - Frequency updates based on risk changes
 * - Failed screening retry logic
 * - Performance monitoring and reporting
 * 
 * @package Nexus\Sanctions\Contracts
 */
interface PeriodicScreeningManagerInterface
{
    /**
     * Schedule periodic screening for a party.
     *
     * Registers party for automatic re-screening at specified frequency.
     * Frequency should be based on party risk level per FATF guidelines.
     *
     * @param string $partyId Party ID to schedule
     * @param ScreeningFrequency $frequency Screening frequency
     * @param array<string, mixed> $options Scheduling options:
     *        - 'lists' => array<SanctionsList> (default: all lists)
     *        - 'start_date' => \DateTimeImmutable (default: now)
     *        - 'priority' => int (1-10, default: 5)
     * @return string Schedule ID
     * @throws \Nexus\Sanctions\Exceptions\InvalidPartyException If party not found
     */
    public function scheduleScreening(
        string $partyId,
        ScreeningFrequency $frequency,
        array $options = []
    ): string;

    /**
     * Execute all scheduled screenings due now.
     *
     * Processes all parties with screening due <= current time.
     * Runs in batches for efficiency.
     *
     * @param int $batchSize Parties to process per batch (default: 100)
     * @return array<ScreeningResult> Results of executed screenings
     * @throws \Nexus\Sanctions\Exceptions\ScreeningFailedException If batch fails
     */
    public function executeScheduledScreenings(int $batchSize = 100): array;

    /**
     * Update screening frequency for a party.
     *
     * Changes frequency based on risk reassessment.
     * Adjusts next screening date accordingly.
     *
     * @param string $partyId Party ID
     * @param ScreeningFrequency $newFrequency New screening frequency
     * @return void
     * @throws \Nexus\Sanctions\Exceptions\InvalidPartyException If party not found
     */
    public function updateScreeningFrequency(
        string $partyId,
        ScreeningFrequency $newFrequency
    ): void;

    /**
     * Get next scheduled screening date for a party.
     *
     * Returns when party will next be screened.
     * Null if party has no scheduled screening.
     *
     * @param string $partyId Party ID
     * @return \DateTimeImmutable|null Next screening date or null
     */
    public function getNextScreeningDate(string $partyId): ?\DateTimeImmutable;

    /**
     * Cancel scheduled screening for a party.
     *
     * Removes party from periodic screening schedule.
     * Should be called when party relationship ends.
     *
     * @param string $partyId Party ID
     * @return bool True if cancelled successfully
     */
    public function cancelScheduledScreening(string $partyId): bool;

    /**
     * Get screening schedule details for a party.
     *
     * Returns current schedule configuration.
     *
     * @param string $partyId Party ID
     * @return array|null Schedule details:
     *         - 'frequency' => ScreeningFrequency
     *         - 'next_screening_date' => \DateTimeImmutable
     *         - 'last_screening_date' => \DateTimeImmutable|null
     *         - 'lists' => array<SanctionsList>
     *         - 'priority' => int
     */
    public function getScheduleDetails(string $partyId): ?array;

    /**
     * Get all parties due for screening.
     *
     * Returns parties with screening date <= specified date.
     * Useful for scheduling and monitoring.
     *
     * @param \DateTimeImmutable|null $dueDate Date to check (default: now)
     * @param int $limit Maximum parties to return (default: 1000)
     * @return array<string> Array of party IDs
     */
    public function getPartiesDueForScreening(
        ?\DateTimeImmutable $dueDate = null,
        int $limit = 1000
    ): array;

    /**
     * Get screening execution statistics.
     *
     * Returns metrics about scheduled screening performance.
     * Useful for monitoring and reporting.
     *
     * @param \DateTimeImmutable $fromDate Statistics start date
     * @param \DateTimeImmutable $toDate Statistics end date
     * @return array Statistics:
     *         - 'total_scheduled' => int
     *         - 'executed' => int
     *         - 'failed' => int
     *         - 'matches_found' => int
     *         - 'average_duration_ms' => float
     *         - 'by_frequency' => array<string, int>
     */
    public function getExecutionStatistics(
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate
    ): array;

    /**
     * Retry failed screenings.
     *
     * Re-attempts screenings that failed due to transient errors.
     * Useful for handling temporary API failures.
     *
     * @param int $maxRetries Maximum retry attempts (default: 3)
     * @return array<ScreeningResult> Results of retried screenings
     */
    public function retryFailedScreenings(int $maxRetries = 3): array;

    /**
     * Schedule immediate screening for a party.
     *
     * Bypasses normal schedule for urgent screening.
     * Useful for risk-event triggered screenings.
     *
     * @param string $partyId Party ID
     * @param string $reason Reason for immediate screening
     * @return ScreeningResult
     * @throws \Nexus\Sanctions\Exceptions\ScreeningFailedException If screening fails
     */
    public function scheduleImmediateScreening(
        string $partyId,
        string $reason
    ): ScreeningResult;

    /**
     * Bulk schedule screening for multiple parties.
     *
     * Efficiently schedules screening for many parties at once.
     * Useful for initial onboarding or bulk updates.
     *
     * @param array<string> $partyIds Party IDs to schedule
     * @param ScreeningFrequency $frequency Screening frequency
     * @param array<string, mixed> $options Scheduling options (same as scheduleScreening())
     * @return array<string, string> Schedule IDs keyed by party ID
     */
    public function bulkScheduleScreening(
        array $partyIds,
        ScreeningFrequency $frequency,
        array $options = []
    ): array;

    /**
     * Get screening metrics
     *
     * Returns comprehensive metrics about screening operations including
     * counts by status, match statistics, and performance data.
     *
     * @param \DateTimeImmutable|null $fromDate Start date for metrics (default: 30 days ago)
     * @param \DateTimeImmutable|null $toDate End date for metrics (default: now)
     * @return array<string, mixed> Metrics data including:
     *         - total_screened: int
     *         - matches_found: int
     *         - pending_review: int
     *         - confirmed_matches: int
     *         - false_positives: int
     *         - average_processing_time_ms: float
     */
    public function getScreeningMetrics(
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array;

    /**
     * Check if party has active matches
     *
     * Determines if a party currently has any unresolved/active sanctions matches
     * that require attention.
     *
     * @param string $partyId Party ID to check
     * @return bool True if party has active matches requiring review
     */
    public function hasActiveMatches(string $partyId): bool;

    /**
     * Get pending reviews
     *
     * Returns all screenings that have matches pending manual review.
     *
     * @param int $limit Maximum number of results to return (default: 100)
     * @return array<ScreeningResult> Array of screening results with pending matches
     */
    public function getPendingReviews(int $limit = 100): array;
}
