<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services;

use Nexus\DataPrivacy\Contracts\ProcessingActivityManagerInterface;
use Nexus\DataPrivacy\Contracts\ProcessingActivityQueryInterface;
use Nexus\DataPrivacy\Contracts\ProcessingActivityPersistInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\ValueObjects\ProcessingActivity;
use Nexus\DataPrivacy\Enums\LawfulBasisType;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Exceptions\ProcessingActivityNotFoundException;
use Nexus\DataPrivacy\Exceptions\InvalidProcessingActivityException;

/**
 * Manages processing activity records (ROPA - Record of Processing Activities).
 */
final readonly class ProcessingActivityManager implements ProcessingActivityManagerInterface
{
    public function __construct(
        private ProcessingActivityQueryInterface $activityQuery,
        private ProcessingActivityPersistInterface $activityPersist,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function registerActivity(
        string $name,
        string $purpose,
        LawfulBasisType $lawfulBasis,
        array $dataCategories,
        string $controllerName,
        ?string $processorName = null,
        bool $crossBorderTransfer = false,
        bool $automatedDecisionMaking = false
    ): ProcessingActivity {
        if (empty($name)) {
            throw new InvalidProcessingActivityException('Activity name is required');
        }

        if (empty($purpose)) {
            throw new InvalidProcessingActivityException('Purpose is required');
        }

        if (empty($dataCategories)) {
            throw new InvalidProcessingActivityException(
                'At least one data category is required'
            );
        }

        $activity = ProcessingActivity::create(
            id: $this->generateActivityId(),
            name: $name,
            purpose: $purpose,
            lawfulBasis: $lawfulBasis,
            dataCategories: $dataCategories,
            controllerName: $controllerName,
            processorName: $processorName,
            crossBorderTransfer: $crossBorderTransfer,
            automatedDecisionMaking: $automatedDecisionMaking
        );

        $activityId = $this->activityPersist->save($activity);

        $this->auditLogger?->log(
            'processing_activity',
            $activityId,
            'registered',
            "Processing activity registered: {$name}",
            [
                'purpose' => $purpose,
                'lawful_basis' => $lawfulBasis->value,
                'data_categories' => array_map(fn($c) => $c->value, $dataCategories),
                'cross_border' => $crossBorderTransfer,
                'automated_decisions' => $automatedDecisionMaking,
            ]
        );

        return $activity;
    }

    public function getActivity(string $activityId): ProcessingActivity
    {
        $activity = $this->activityQuery->findById($activityId);

        if ($activity === null) {
            throw ProcessingActivityNotFoundException::withId($activityId);
        }

        return $activity;
    }

    public function getAllActivities(): array
    {
        return $this->activityQuery->findAll();
    }

    public function getActiveActivities(): array
    {
        return $this->activityQuery->findActive();
    }

    public function updateActivity(
        string $activityId,
        string $purpose,
        LawfulBasisType $lawfulBasis,
        array $dataCategories
    ): ProcessingActivity {
        $activity = $this->getActivity($activityId);

        if (empty($dataCategories)) {
            throw new InvalidProcessingActivityException(
                'At least one data category is required'
            );
        }

        $updatedActivity = ProcessingActivity::create(
            id: $activityId,
            name: $activity->getName(),
            purpose: $purpose,
            lawfulBasis: $lawfulBasis,
            dataCategories: $dataCategories,
            controllerName: $activity->getControllerName(),
            processorName: $activity->getProcessorName(),
            crossBorderTransfer: $activity->hasCrossBorderTransfer(),
            automatedDecisionMaking: $activity->hasAutomatedDecisionMaking()
        );

        $this->activityPersist->update($updatedActivity);

        $this->auditLogger?->log(
            'processing_activity',
            $activityId,
            'updated',
            "Processing activity updated: {$activity->getName()}",
            [
                'old_purpose' => $activity->getPurpose(),
                'new_purpose' => $purpose,
                'old_lawful_basis' => $activity->getLawfulBasis()->value,
                'new_lawful_basis' => $lawfulBasis->value,
            ]
        );

        return $updatedActivity;
    }

    public function deactivateActivity(
        string $activityId,
        string $reason
    ): ProcessingActivity {
        $activity = $this->getActivity($activityId);

        if (!$activity->isActive()) {
            throw new InvalidProcessingActivityException('Activity is already inactive');
        }

        $deactivatedActivity = $activity->deactivate();
        $this->activityPersist->deactivate($activityId, $reason);

        $this->auditLogger?->log(
            'processing_activity',
            $activityId,
            'deactivated',
            "Processing activity deactivated: {$reason}",
            ['reason' => $reason]
        );

        return $deactivatedActivity;
    }

    public function reactivateActivity(string $activityId): ProcessingActivity
    {
        $activity = $this->getActivity($activityId);

        if ($activity->isActive()) {
            throw new InvalidProcessingActivityException('Activity is already active');
        }

        $reactivatedActivity = $this->activityPersist->reactivate($activityId);

        $this->auditLogger?->log(
            'processing_activity',
            $activityId,
            'reactivated',
            'Processing activity reactivated',
            []
        );

        return $reactivatedActivity;
    }

    public function markReviewed(
        string $activityId,
        string $reviewedBy,
        ?string $notes = null
    ): ProcessingActivity {
        $activity = $this->getActivity($activityId);
        $reviewedAt = new \DateTimeImmutable();

        $reviewedActivity = $activity->markReviewed($reviewedAt);
        $this->activityPersist->markReviewed($activityId, $reviewedAt, $reviewedBy, $notes);

        $this->auditLogger?->log(
            'processing_activity',
            $activityId,
            'reviewed',
            "Processing activity reviewed by {$reviewedBy}",
            [
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => $reviewedAt->format('Y-m-d H:i:s'),
                'notes' => $notes,
            ]
        );

        return $reviewedActivity;
    }

    public function getActivitiesRequiringDpia(): array
    {
        return $this->activityQuery->findRequiringDpia();
    }

    public function getActivitiesNeedingReview(int $monthsSinceReview = 12): array
    {
        return $this->activityQuery->findNeedingReview($monthsSinceReview);
    }

    public function getActivitiesWithSensitiveData(): array
    {
        $activities = $this->getActiveActivities();

        return array_filter(
            $activities,
            fn(ProcessingActivity $activity) => $activity->processesSensitiveData()
        );
    }

    public function updateDpiaStatus(
        string $activityId,
        bool $dpiaCompleted,
        ?string $dpiaReference = null
    ): void {
        $activity = $this->getActivity($activityId);

        $dpiaDate = $dpiaCompleted ? new \DateTimeImmutable() : null;

        $this->activityPersist->updateDpiaStatus(
            $activityId,
            $dpiaCompleted,
            $dpiaDate,
            $dpiaReference
        );

        $this->auditLogger?->log(
            'processing_activity',
            $activityId,
            'dpia_updated',
            $dpiaCompleted ? 'DPIA completed' : 'DPIA marked as incomplete',
            [
                'dpia_completed' => $dpiaCompleted,
                'dpia_reference' => $dpiaReference,
            ]
        );
    }

    public function exportRopa(): array
    {
        return $this->activityQuery->exportRopa();
    }

    public function getRopaSummary(): array
    {
        $summary = $this->activityQuery->getRopaSummary();
        $needingReview = count($this->getActivitiesNeedingReview());

        return [
            'total_activities' => $summary['total_activities'],
            'active_activities' => count($this->getActiveActivities()),
            'by_lawful_basis' => $summary['by_lawful_basis'],
            'with_cross_border' => $summary['with_cross_border'],
            'with_automated_decisions' => $summary['with_automated_decisions'],
            'requiring_dpia' => $summary['requiring_dpia'],
            'processing_sensitive_data' => $summary['sensitive_data_activities'],
            'needing_review' => $needingReview,
        ];
    }

    private function generateActivityId(): string
    {
        return 'PA-' . date('Ymd') . '-' . strtoupper(substr(md5((string)microtime(true)), 0, 6));
    }
}
