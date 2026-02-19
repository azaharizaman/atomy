<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Workflows;

use Nexus\CRM\Contracts\LeadQueryInterface;
use Nexus\CRM\Contracts\OpportunityQueryInterface;
use Nexus\CRM\Contracts\ActivityQueryInterface;
use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Nexus\CRMOperations\Contracts\AnalyticsProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Sales Playbook Workflow
 * 
 * Provides guided selling steps, event-triggered playbooks, and next best actions.
 * Tracks playbook completion and progress.
 * 
 * @package Nexus\CRMOperations\Workflows
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class SalesPlaybookWorkflow
{
    /**
     * Available playbook types
     */
    public const PLAYBOOK_NEW_LEAD = 'new_lead';
    public const PLAYBOOK_QUALIFICATION = 'qualification';
    public const PLAYBOOK_DISCOVERY = 'discovery';
    public const PLAYBOOK_PROPOSAL = 'proposal';
    public const PLAYBOOK_NEGOTIATION = 'negotiation';
    public const PLAYBOOK_CLOSING = 'closing';
    public const PLAYBOOK_RENEWAL = 'renewal';

    /**
     * @param LeadQueryInterface $leadQuery Lead query service
     * @param OpportunityQueryInterface $opportunityQuery Opportunity query service
     * @param ActivityQueryInterface $activityQuery Activity query service
     * @param NotificationProviderInterface $notificationProvider Notification provider
     * @param AnalyticsProviderInterface $analyticsProvider Analytics provider
     * @param EventDispatcherInterface $eventDispatcher Event dispatcher
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private LeadQueryInterface $leadQuery,
        private OpportunityQueryInterface $opportunityQuery,
        private ActivityQueryInterface $activityQuery,
        private NotificationProviderInterface $notificationProvider,
        private AnalyticsProviderInterface $analyticsProvider,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Get guided selling steps for a playbook
     * 
     * @param string $playbookType Playbook type
     * @param string $entityId Lead or Opportunity ID
     * @return PlaybookStepsResult Result with steps
     */
    public function getGuidedSteps(string $playbookType, string $entityId): PlaybookStepsResult
    {
        $this->logger?->info('Getting guided selling steps', [
            'playbook_type' => $playbookType,
            'entity_id' => $entityId,
        ]);

        $steps = $this->getPlaybookSteps($playbookType, $entityId);
        $completedSteps = $this->getCompletedSteps($entityId, $playbookType);

        return new PlaybookStepsResult(
            playbookType: $playbookType,
            entityId: $entityId,
            steps: $steps,
            completedSteps: $completedSteps,
            progress: count($completedSteps) / count($steps) * 100
        );
    }

    /**
     * Trigger playbook based on event
     * 
     * @param string $eventType Event type (lead_created, stage_changed, etc.)
     * @param string $entityId Lead or Opportunity ID
     * @param array<string, mixed> $context Event context
     * @return PlaybookTriggerResult Result of triggering
     */
    public function triggerPlaybook(
        string $eventType,
        string $entityId,
        array $context = []
    ): PlaybookTriggerResult {
        $this->logger?->info('Triggering playbook on event', [
            'event_type' => $eventType,
            'entity_id' => $entityId,
        ]);

        $playbookType = $this->determinePlaybookForEvent($eventType, $entityId);
        
        if ($playbookType === null) {
            return new PlaybookTriggerResult(
                success: false,
                playbookType: null,
                message: 'No playbook found for event type'
            );
        }

        // Get recommended playbook
        $steps = $this->getPlaybookSteps($playbookType, $entityId);

        // Notify user about new playbook
        $this->notificationProvider->notify(
            userId: $context['owner_id'] ?? 'system',
            subject: "New Playbook: {$playbookType}",
            message: "A new sales playbook has been assigned to help you progress this deal.",
            context: [
                'playbook_type' => $playbookType,
                'entity_id' => $entityId,
                'step_count' => count($steps),
            ]
        );

        return new PlaybookTriggerResult(
            success: true,
            playbookType: $playbookType,
            message: "Playbook '{$playbookType}' triggered successfully"
        );
    }

    /**
     * Get next best actions for an entity
     * 
     * @param string $entityType Entity type (lead, opportunity)
     * @param string $entityId Entity ID
     * @return NextBestActionResult Result with recommended actions
     */
    public function getNextBestActions(string $entityType, string $entityId): NextBestActionResult
    {
        $this->logger?->info('Getting next best actions', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        $recommendations = [];

        // Get entity data
        if ($entityType === 'lead') {
            $entity = $this->leadQuery->findByIdOrFail($entityId);
            $recommendations = $this->generateLeadRecommendations($entity);
        } elseif ($entityType === 'opportunity') {
            $entity = $this->opportunityQuery->findByIdOrFail($entityId);
            $recommendations = $this->generateOpportunityRecommendations($entity);
        }

        return new NextBestActionResult(
            entityType: $entityType,
            entityId: $entityId,
            recommendations: $recommendations,
            generatedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Track playbook completion
     * 
     * @param string $entityId Lead or Opportunity ID
     * @param string $playbookType Playbook type
     * @param string $stepId Step ID completed
     * @return PlaybookCompletionResult Result of tracking
     */
    public function trackCompletion(
        string $entityId,
        string $playbookType,
        string $stepId
    ): PlaybookCompletionResult {
        $this->logger?->info('Tracking playbook completion', [
            'entity_id' => $entityId,
            'playbook_type' => $playbookType,
            'step_id' => $stepId,
        ]);

        // In real implementation, store completion in database
        $completedAt = new \DateTimeImmutable();

        // Track analytics
        $this->analyticsProvider->track('playbook_step_completed', [
            'entity_id' => $entityId,
            'playbook_type' => $playbookType,
            'step_id' => $stepId,
            'completed_at' => $completedAt->format(\DateTimeInterface::ATOM),
        ]);

        // Get updated progress
        $stepsResult = $this->getGuidedSteps($playbookType, $entityId);

        return new PlaybookCompletionResult(
            success: true,
            entityId: $entityId,
            playbookType: $playbookType,
            stepId: $stepId,
            completedAt: $completedAt,
            overallProgress: $stepsResult->progress
        );
    }

    /**
     * Get playbook steps for a type
     * 
     * @param string $playbookType Playbook type
     * @param string $entityId Entity ID
     * @return array<int, array{id: string, title: string, description: string, order: int}>
     */
    private function getPlaybookSteps(string $playbookType, string $entityId): array
    {
        $steps = [
            self::PLAYBOOK_NEW_LEAD => [
                ['id' => 'step_1', 'title' => 'Verify Lead Information', 'description' => 'Confirm contact details and company information', 'order' => 1],
                ['id' => 'step_2', 'title' => 'Initial Outreach', 'description' => 'Make first contact within 24 hours', 'order' => 2],
                ['id' => 'step_3', 'title' => 'Qualify the Lead', 'description' => 'Assess if lead meets ideal customer criteria', 'order' => 3],
            ],
            self::PLAYBOOK_QUALIFICATION => [
                ['id' => 'step_1', 'title' => 'Identify Pain Points', 'description' => 'Understand the customer\'s challenges', 'order' => 1],
                ['id' => 'step_2', 'title' => 'Define Budget', 'description' => 'Discuss budget and purchasing timeline', 'order' => 2],
                ['id' => 'step_3', 'title' => 'Identify Decision Makers', 'description' => 'Map out the buying committee', 'order' => 3],
                ['id' => 'step_4', 'title' => 'Assess Fit', 'description' => 'Evaluate product-market fit', 'order' => 4],
            ],
            self::PLAYBOOK_DISCOVERY => [
                ['id' => 'step_1', 'title' => 'Schedule Discovery Call', 'description' => 'Set up a detailed discovery session', 'order' => 1],
                ['id' => 'step_2', 'title' => 'Gather Requirements', 'description' => 'Document all business requirements', 'order' => 2],
                ['id' => 'step_3', 'title' => 'Present Solution Overview', 'description' => 'Provide initial solution proposal', 'order' => 3],
            ],
            self::PLAYBOOK_PROPOSAL => [
                ['id' => 'step_1', 'title' => 'Prepare Proposal', 'description' => 'Create detailed proposal document', 'order' => 1],
                ['id' => 'step_2', 'title' => 'Internal Review', 'description' => 'Get proposal approved internally', 'order' => 2],
                ['id' => 'step_3', 'title' => 'Present Proposal', 'description' => 'Present proposal to stakeholders', 'order' => 3],
            ],
            self::PLAYBOOK_NEGOTIATION => [
                ['id' => 'step_1', 'title' => 'Address Objections', 'description' => 'Handle any concerns or objections', 'order' => 1],
                ['id' => 'step_2', 'title' => 'Negotiate Terms', 'description' => 'Discuss pricing and contract terms', 'order' => 2],
                ['id' => 'step_3', 'title' => 'Get Approval', 'description' => 'Obain final approval from decision makers', 'order' => 3],
            ],
            self::PLAYBOOK_CLOSING => [
                ['id' => 'step_1', 'title' => 'Prepare Contract', 'description' => 'Generate final contract', 'order' => 1],
                ['id' => 'step_2', 'title' => 'Obtain Signatures', 'description' => 'Get all required signatures', 'order' => 2],
                ['id' => 'step_3', 'title' => 'Handoff to CS', 'description' => 'Transfer to customer success team', 'order' => 3],
            ],
            self::PLAYBOOK_RENEWAL => [
                ['id' => 'step_1', 'title' => 'Review Account Health', 'description' => 'Assess customer satisfaction and usage', 'order' => 1],
                ['id' => 'step_2', 'title' => 'Identify Upsell Opportunities', 'description' => 'Find expansion opportunities', 'order' => 2],
                ['id' => 'step_3', 'title' => 'Prepare Renewal Proposal', 'description' => 'Create renewal and upgrade offer', 'order' => 3],
            ],
        ];

        return $steps[$playbookType] ?? [];
    }

    /**
     * Get completed steps for an entity
     * 
     * @param string $entityId Entity ID
     * @param string $playbookType Playbook type
     * @return string[]
     */
    private function getCompletedSteps(string $entityId, string $playbookType): array
    {
        // In real implementation, query database for completed steps
        return [];
    }

    /**
     * Determine playbook for event
     * 
     * @param string $eventType Event type
     * @param string $entityId Entity ID
     * @return string|null Playbook type
     */
    private function determinePlaybookForEvent(string $eventType, string $entityId): ?string
    {
        $eventMapping = [
            'lead_created' => self::PLAYBOOK_NEW_LEAD,
            'lead_status_changed' => self::PLAYBOOK_QUALIFICATION,
            'opportunity_stage_changed' => self::PLAYBOOK_DISCOVERY,
            'proposal_sent' => self::PLAYBOOK_PROPOSAL,
            'negotiation_started' => self::PLAYBOOK_NEGOTIATION,
            'deal_won' => self::PLAYBOOK_CLOSING,
            'contract_renewal_due' => self::PLAYBOOK_RENEWAL,
        ];

        return $eventMapping[$eventType] ?? null;
    }

    /**
     * Generate recommendations for a lead
     * 
     * @param object $lead Lead entity
     * @return array<int, array{action: string, reason: string, priority: string}>
     */
    private function generateLeadRecommendations(object $lead): array
    {
        $recommendations = [];

        // Recommend first contact if not done
        $activities = $this->activityQuery->findByLead($lead->getId());
        $activityList = [];
        foreach ($activities as $activity) {
            $activityList[] = $activity;
        }
        if (empty($activityList)) {
            $recommendations[] = [
                'action' => 'Schedule first call',
                'reason' => 'No activity recorded yet',
                'priority' => 'high',
            ];
        }

        // Recommend follow-up if stale
        $recommendations[] = [
            'action' => 'Send follow-up email',
            'reason' => 'Keep engagement high',
            'priority' => 'medium',
        ];

        return $recommendations;
    }

    /**
     * Generate recommendations for an opportunity
     * 
     * @param object $opportunity Opportunity entity
     * @return array<int, array{action: string, reason: string, priority: string}>
     */
    private function generateOpportunityRecommendations(object $opportunity): array
    {
        $recommendations = [];

        // Recommend next steps based on stage
        $stage = $opportunity->getStage();
        
        $stageRecommendations = [
            'qualification' => ['Schedule discovery call', 'Send qualification questionnaire'],
            'discovery' => ['Prepare proposal', 'Schedule demo'],
            'proposal' => ['Follow up on proposal', 'Address any questions'],
            'negotiation' => ['Prepare contract', 'Negotiate terms'],
            'closed_won' => ['Handoff to customer success', 'Schedule onboarding'],
        ];

        if (isset($stageRecommendations[$stage])) {
            foreach ($stageRecommendations[$stage] as $action) {
                $recommendations[] = [
                    'action' => $action,
                    'reason' => "Current stage: {$stage}",
                    'priority' => 'high',
                ];
            }
        }

        return $recommendations;
    }
}

/**
 * Result DTO for guided steps
 */
final readonly class PlaybookStepsResult
{
    /**
     * @param string $playbookType Playbook type
     * @param string $entityId Entity ID
     * @param array<int, array{id: string, title: string, description: string, order: int}> $steps All steps
     * @param string[] $completedSteps Completed step IDs
     * @param float $progress Overall progress percentage
     */
    public function __construct(
        public string $playbookType,
        public string $entityId,
        public array $steps,
        public array $completedSteps,
        public float $progress
    ) {}
}

/**
 * Result DTO for playbook trigger
 */
final readonly class PlaybookTriggerResult
{
    /**
     * @param bool $success Whether trigger was successful
     * @param string|null $playbookType Playbook type triggered
     * @param string $message Result message
     */
    public function __construct(
        public bool $success,
        public ?string $playbookType,
        public string $message
    ) {}
}

/**
 * Result DTO for next best actions
 */
final readonly class NextBestActionResult
{
    /**
     * @param string $entityType Entity type
     * @param string $entityId Entity ID
     * @param array<int, array{action: string, reason: string, priority: string}> $recommendations Recommendations
     * @param \DateTimeImmutable $generatedAt Generation timestamp
     */
    public function __construct(
        public string $entityType,
        public string $entityId,
        public array $recommendations,
        public \DateTimeImmutable $generatedAt
    ) {}
}

/**
 * Result DTO for playbook completion tracking
 */
final readonly class PlaybookCompletionResult
{
    /**
     * @param bool $success Whether tracking was successful
     * @param string $entityId Entity ID
     * @param string $playbookType Playbook type
     * @param string $stepId Completed step ID
     * @param \DateTimeImmutable $completedAt Completion timestamp
     * @param float $overallProgress Overall progress percentage
     */
    public function __construct(
        public bool $success,
        public string $entityId,
        public string $playbookType,
        public string $stepId,
        public \DateTimeImmutable $completedAt,
        public float $overallProgress
    ) {}
}
