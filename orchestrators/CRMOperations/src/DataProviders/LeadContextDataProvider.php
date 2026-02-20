<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\DataProviders;

use Nexus\CRM\Contracts\LeadInterface;
use Nexus\CRM\Contracts\LeadQueryInterface;
use Nexus\CRM\Contracts\ActivityQueryInterface;

/**
 * Lead Context Data Provider
 * 
 * Provides enriched lead context data for workflows and rules.
 * Aggregates data from multiple sources.
 * 
 * @package Nexus\CRMOperations\DataProviders
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class LeadContextDataProvider
{
    /**
     * @param LeadQueryInterface $leadQuery Lead query service
     * @param ActivityQueryInterface $activityQuery Activity query service
     */
    public function __construct(
        private LeadQueryInterface $leadQuery,
        private ActivityQueryInterface $activityQuery
    ) {}

    /**
     * Get enriched lead context
     * 
     * @param string $leadId Lead ID
     * @return array<string, mixed> Enriched context data
     */
    public function getContext(string $leadId): array
    {
        $lead = $this->leadQuery->findById($leadId);
        
        if ($lead === null) {
            return [];
        }

        return [
            'lead' => $this->extractLeadData($lead),
            'activities' => $this->getActivityData($leadId),
            'metrics' => $this->calculateMetrics($lead),
        ];
    }

    /**
     * Get lead data for conversion
     * 
     * @param string $leadId Lead ID
     * @return array<string, mixed>|null Lead data
     */
    public function getLeadDataForConversion(string $leadId): ?array
    {
        $lead = $this->leadQuery->findById($leadId);
        
        if ($lead === null || !$lead->isConvertible()) {
            return null;
        }

        return [
            'id' => $lead->getId(),
            'tenant_id' => $lead->getTenantId(),
            'title' => $lead->getTitle(),
            'description' => $lead->getDescription(),
            'status' => $lead->getStatus()->value,
            'source' => $lead->getSource()->value,
            'estimated_value' => $lead->getEstimatedValue(),
            'currency' => $lead->getCurrency(),
            'score' => $lead->getScore()?->getValue(),
            'external_ref' => $lead->getExternalRef(),
            'created_at' => $lead->getCreatedAt()->format('c'),
            'age_days' => $this->calculateAgeInDays($lead),
        ];
    }

    /**
     * Extract lead data
     */
    private function extractLeadData(LeadInterface $lead): array
    {
        return [
            'id' => $lead->getId(),
            'title' => $lead->getTitle(),
            'status' => $lead->getStatus()->value,
            'source' => $lead->getSource()->value,
            'is_qualified' => $lead->isQualified(),
            'is_convertible' => $lead->isConvertible(),
            'estimated_value' => $lead->getEstimatedValue(),
            'currency' => $lead->getCurrency(),
        ];
    }

    /**
     * Get activity data for lead
     */
    private function getActivityData(string $leadId): array
    {
        $activities = $this->activityQuery->findByLead($leadId);
        
        $data = [
            'total_count' => 0,
            'by_type' => [],
            'last_activity' => null,
        ];

        foreach ($activities as $activity) {
            $type = $activity->getType()->value;
            
            $data['total_count']++;
            $data['by_type'][$type] = ($data['by_type'][$type] ?? 0) + 1;
            
            if ($data['last_activity'] === null || 
                $activity->getCreatedAt() > new \DateTimeImmutable($data['last_activity'])) {
                $data['last_activity'] = $activity->getCreatedAt()->format('c');
            }
        }

        return $data;
    }

    /**
     * Calculate lead metrics
     */
    private function calculateMetrics(LeadInterface $lead): array
    {
        return [
            'age_days' => $this->calculateAgeInDays($lead),
            'score' => $lead->getScore()?->getValue(),
            'quality_tier' => $lead->getScore()?->getQualityTier(),
        ];
    }

    /**
     * Calculate lead age in days
     */
    private function calculateAgeInDays(LeadInterface $lead): int
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($lead->getCreatedAt());
        return $diff->days;
    }
}