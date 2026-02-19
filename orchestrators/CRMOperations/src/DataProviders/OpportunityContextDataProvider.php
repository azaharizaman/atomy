<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\DataProviders;

use Nexus\CRM\Contracts\OpportunityInterface;
use Nexus\CRM\Contracts\OpportunityQueryInterface;
use Nexus\CRM\Contracts\ActivityQueryInterface;

/**
 * Opportunity Context Data Provider
 * 
 * Provides enriched opportunity context data for workflows and rules.
 * Aggregates data from multiple sources.
 * 
 * @package Nexus\CRMOperations\DataProviders
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class OpportunityContextDataProvider
{
    /**
     * @param OpportunityQueryInterface $opportunityQuery Opportunity query service
     * @param ActivityQueryInterface $activityQuery Activity query service
     */
    public function __construct(
        private OpportunityQueryInterface $opportunityQuery,
        private ActivityQueryInterface $activityQuery
    ) {}

    /**
     * Get enriched opportunity context
     * 
     * @param string $opportunityId Opportunity ID
     * @return array<string, mixed> Enriched context data
     */
    public function getContext(string $opportunityId): array
    {
        $opportunity = $this->opportunityQuery->findById($opportunityId);
        
        if ($opportunity === null) {
            return [];
        }

        return [
            'opportunity' => $this->extractOpportunityData($opportunity),
            'activities' => $this->getActivityData($opportunityId),
            'metrics' => $this->calculateMetrics($opportunity),
        ];
    }

    /**
     * Get opportunity data for closing
     * 
     * @param string $opportunityId Opportunity ID
     * @return array<string, mixed>|null Opportunity data
     */
    public function getOpportunityDataForClosing(string $opportunityId): ?array
    {
        $opportunity = $this->opportunityQuery->findById($opportunityId);
        
        if ($opportunity === null || !$opportunity->isOpen()) {
            return null;
        }

        return [
            'id' => $opportunity->getId(),
            'tenant_id' => $opportunity->getTenantId(),
            'pipeline_id' => $opportunity->getPipelineId(),
            'title' => $opportunity->getTitle(),
            'description' => $opportunity->getDescription(),
            'stage' => $opportunity->getStage()->value,
            'value' => $opportunity->getValue(),
            'currency' => $opportunity->getCurrency(),
            'expected_close_date' => $opportunity->getExpectedCloseDate()->format('c'),
            'probability' => $opportunity->getForecastProbability()->getPercentage(),
            'weighted_value' => $opportunity->getWeightedValue(),
            'source_lead_id' => $opportunity->getSourceLeadId(),
            'created_at' => $opportunity->getCreatedAt()->format('c'),
            'age_days' => $opportunity->getAgeInDays(),
            'days_in_stage' => $opportunity->getDaysInCurrentStage(),
        ];
    }

    /**
     * Extract opportunity data
     */
    private function extractOpportunityData(OpportunityInterface $opportunity): array
    {
        return [
            'id' => $opportunity->getId(),
            'title' => $opportunity->getTitle(),
            'stage' => $opportunity->getStage()->value,
            'value' => $opportunity->getValue(),
            'currency' => $opportunity->getCurrency(),
            'is_open' => $opportunity->isOpen(),
            'is_won' => $opportunity->isWon(),
            'is_lost' => $opportunity->isLost(),
            'probability' => $opportunity->getForecastProbability()->getPercentage(),
            'weighted_value' => $opportunity->getWeightedValue(),
        ];
    }

    /**
     * Get activity data for opportunity
     */
    private function getActivityData(string $opportunityId): array
    {
        $activities = $this->activityQuery->findByOpportunity($opportunityId);
        
        $data = [
            'total_count' => 0,
            'by_type' => [],
            'last_activity' => null,
            'has_recent_activity' => false,
        ];

        $recentThreshold = new \DateTimeImmutable('-7 days');

        foreach ($activities as $activity) {
            $type = $activity->getType()->value;
            
            $data['total_count']++;
            $data['by_type'][$type] = ($data['by_type'][$type] ?? 0) + 1;
            
            if ($data['last_activity'] === null || 
                $activity->getCreatedAt() > new \DateTimeImmutable($data['last_activity'])) {
                $data['last_activity'] = $activity->getCreatedAt()->format('c');
            }

            if ($activity->getCreatedAt() >= $recentThreshold) {
                $data['has_recent_activity'] = true;
            }
        }

        return $data;
    }

    /**
     * Calculate opportunity metrics
     */
    private function calculateMetrics(OpportunityInterface $opportunity): array
    {
        return [
            'age_days' => $opportunity->getAgeInDays(),
            'days_in_stage' => $opportunity->getDaysInCurrentStage(),
            'is_stale' => $opportunity->getDaysInCurrentStage() > 30,
            'probability' => $opportunity->getForecastProbability()->getPercentage(),
            'weighted_value' => $opportunity->getWeightedValue(),
        ];
    }

    /**
     * Check if opportunity needs attention
     * 
     * @param string $opportunityId Opportunity ID
     * @return array<string, mixed> Attention indicators
     */
    public function getAttentionIndicators(string $opportunityId): array
    {
        $context = $this->getContext($opportunityId);
        
        if (empty($context)) {
            return [];
        }

        $indicators = [];

        // Check if stale
        if ($context['metrics']['is_stale'] ?? false) {
            $indicators[] = [
                'type' => 'stale',
                'message' => 'Opportunity has been in current stage for over 30 days',
                'severity' => 'warning',
            ];
        }

        // Check for no recent activity
        if (!($context['activities']['has_recent_activity'] ?? true)) {
            $indicators[] = [
                'type' => 'no_activity',
                'message' => 'No activity in the last 7 days',
                'severity' => 'info',
            ];
        }

        // Check for approaching close date
        $expectedCloseDate = $context['opportunity']['expected_close_date'] ?? null;
        if ($expectedCloseDate) {
            $closeDate = new \DateTimeImmutable($expectedCloseDate);
            $now = new \DateTimeImmutable();
            $daysUntilClose = $now->diff($closeDate)->days;
            
            if ($daysUntilClose <= 7 && $daysUntilClose >= 0) {
                $indicators[] = [
                    'type' => 'closing_soon',
                    'message' => sprintf('Expected to close in %d days', $daysUntilClose),
                    'severity' => 'info',
                ];
            } elseif ($daysUntilClose < 0) {
                $indicators[] = [
                    'type' => 'overdue',
                    'message' => sprintf('Close date passed %d days ago', abs($daysUntilClose)),
                    'severity' => 'warning',
                ];
            }
        }

        return $indicators;
    }
}