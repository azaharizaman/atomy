<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Rules;

use Nexus\CRM\Contracts\LeadInterface;
use Nexus\CRM\Enums\LeadStatus;

/**
 * Lead Qualification Rule
 * 
 * Evaluates whether a lead meets qualification criteria.
 * Business rule for lead qualification decisions.
 * 
 * @package Nexus\CRMOperations\Rules
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class LeadQualificationRule
{
    /**
     * Default qualification thresholds
     */
    private const DEFAULT_THRESHOLDS = [
        'minimum_score' => 50,
        'minimum_activities' => 2,
        'maximum_age_days' => 90,
    ];

    /**
     * @param array<string, mixed> $thresholds Custom thresholds
     */
    public function __construct(
        private readonly array $thresholds = self::DEFAULT_THRESHOLDS
    ) {}

    /**
     * Evaluate if lead is qualified
     * 
     * @param LeadInterface $lead Lead to evaluate
     * @param array<string, mixed> $context Additional context
     * @return QualificationResult Evaluation result
     */
    public function evaluate(LeadInterface $lead, array $context = []): QualificationResult
    {
        $reasons = [];
        $passed = true;

        // Check lead status
        if (!$lead->getStatus()->isActive()) {
            return new QualificationResult(
                qualified: false,
                reasons: ['Lead is not in an active status'],
                score: 0
            );
        }

        // Check lead score
        $score = $lead->getScore()?->getValue() ?? 0;
        $minimumScore = $this->thresholds['minimum_score'] ?? 50;
        
        if ($score < $minimumScore) {
            $passed = false;
            $reasons[] = sprintf(
                'Lead score (%d) is below minimum threshold (%d)',
                $score,
                $minimumScore
            );
        }

        // Check activity count
        $activityCount = $context['activity_count'] ?? 0;
        $minimumActivities = $this->thresholds['minimum_activities'] ?? 2;
        
        if ($activityCount < $minimumActivities) {
            $passed = false;
            $reasons[] = sprintf(
                'Insufficient activities (%d). Minimum required: %d',
                $activityCount,
                $minimumActivities
            );
        }

        // Check lead age
        $ageInDays = $this->calculateAgeInDays($lead);
        $maximumAge = $this->thresholds['maximum_age_days'] ?? 90;
        
        if ($ageInDays > $maximumAge) {
            $reasons[] = sprintf(
                'Lead is %d days old (maximum: %d). Consider disqualifying stale leads.',
                $ageInDays,
                $maximumAge
            );
            // This is a warning, not a failure
        }

        // Check for estimated value
        if ($lead->getEstimatedValue() === null) {
            $reasons[] = 'No estimated value provided. Consider adding for better forecasting.';
        }

        return new QualificationResult(
            qualified: $passed,
            reasons: $reasons,
            score: $score
        );
    }

    /**
     * Check if lead can be qualified
     * 
     * @param LeadInterface $lead Lead to check
     * @param array<string, mixed> $context Additional context
     */
    public function canQualify(LeadInterface $lead, array $context = []): bool
    {
        return $this->evaluate($lead, $context)->qualified;
    }

    /**
     * Get qualification requirements
     * 
     * @return array<string, mixed> Requirements
     */
    public function getRequirements(): array
    {
        return [
            'minimum_score' => $this->thresholds['minimum_score'] ?? 50,
            'minimum_activities' => $this->thresholds['minimum_activities'] ?? 2,
            'maximum_age_days' => $this->thresholds['maximum_age_days'] ?? 90,
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

/**
 * Qualification Result DTO
 */
final readonly class QualificationResult
{
    /**
     * @param bool $qualified Whether lead is qualified
     * @param array<string> $reasons Reasons for the result
     * @param int $score Lead score
     */
    public function __construct(
        public bool $qualified,
        public array $reasons,
        public int $score
    ) {}
}