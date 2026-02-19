<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\ProcessingActivity;
use Nexus\DataPrivacy\Enums\LawfulBasisType;
use Nexus\DataPrivacy\Enums\DataCategory;

/**
 * Manager interface for processing activity operations (ROPA).
 */
interface ProcessingActivityManagerInterface
{
    /**
     * Register a new processing activity.
     *
     * @param array<DataCategory> $dataCategories
     */
    public function registerActivity(
        string $name,
        string $purpose,
        LawfulBasisType $lawfulBasis,
        array $dataCategories,
        string $controllerName,
        ?string $processorName = null,
        bool $crossBorderTransfer = false,
        bool $automatedDecisionMaking = false
    ): ProcessingActivity;

    /**
     * Get activity by ID.
     */
    public function getActivity(string $activityId): ProcessingActivity;

    /**
     * Get all activities.
     *
     * @return array<ProcessingActivity>
     */
    public function getAllActivities(): array;

    /**
     * Get active activities.
     *
     * @return array<ProcessingActivity>
     */
    public function getActiveActivities(): array;

    /**
     * Update processing activity.
     */
    public function updateActivity(
        string $activityId,
        string $purpose,
        LawfulBasisType $lawfulBasis,
        array $dataCategories
    ): ProcessingActivity;

    /**
     * Deactivate a processing activity.
     */
    public function deactivateActivity(
        string $activityId,
        string $reason
    ): ProcessingActivity;

    /**
     * Reactivate a processing activity.
     */
    public function reactivateActivity(string $activityId): ProcessingActivity;

    /**
     * Mark activity as reviewed.
     */
    public function markReviewed(
        string $activityId,
        string $reviewedBy,
        ?string $notes = null
    ): ProcessingActivity;

    /**
     * Get activities requiring DPIA.
     *
     * @return array<ProcessingActivity>
     */
    public function getActivitiesRequiringDpia(): array;

    /**
     * Get activities needing review.
     *
     * @param int $monthsSinceReview Activities not reviewed in this many months
     * @return array<ProcessingActivity>
     */
    public function getActivitiesNeedingReview(int $monthsSinceReview = 12): array;

    /**
     * Get activities processing sensitive data.
     *
     * @return array<ProcessingActivity>
     */
    public function getActivitiesWithSensitiveData(): array;

    /**
     * Update DPIA status.
     */
    public function updateDpiaStatus(
        string $activityId,
        bool $dpiaCompleted,
        ?string $dpiaReference = null
    ): void;

    /**
     * Export ROPA (Record of Processing Activities).
     *
     * @return array<array<string, mixed>>
     */
    public function exportRopa(): array;

    /**
     * Get ROPA summary for compliance reporting.
     *
     * @return array{
     *     total_activities: int,
     *     active_activities: int,
     *     by_lawful_basis: array<string, int>,
     *     with_cross_border: int,
     *     with_automated_decisions: int,
     *     requiring_dpia: int,
     *     processing_sensitive_data: int,
     *     needing_review: int
     * }
     */
    public function getRopaSummary(): array;

    /**
     * Get activities by data subject
     *
     * Returns all processing activities that involve a specific data subject.
     * Useful for handling data subject access requests (DSARs).
     *
     * @param \Nexus\DataPrivacy\ValueObjects\DataSubjectId $dataSubjectId The data subject ID
     * @return array<ProcessingActivity> Activities involving this data subject
     */
    public function getActivitiesByDataSubject(\Nexus\DataPrivacy\ValueObjects\DataSubjectId $dataSubjectId): array;
}
