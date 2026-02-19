<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Rules;

use Nexus\CRM\Contracts\LeadInterface;
use Nexus\CRM\Contracts\OpportunityInterface;

/**
 * SLA Breach Rule
 * 
 * Detects and evaluates SLA breaches for leads and opportunities.
 * Business rule for SLA monitoring and escalation.
 * 
 * @package Nexus\CRMOperations\Rules
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class SLABreachRule
{
    /**
     * Default SLA thresholds (in hours)
     */
    private const DEFAULT_SLA_THRESHOLDS = [
        'lead_first_contact' => 24,        // First contact within 24 hours
        'lead_qualification' => 72,        // Qualification within 72 hours
        'opportunity_follow_up' => 48,     // Follow-up within 48 hours
        'opportunity_proposal' => 168,     // Proposal within 7 days
        'opportunity_close_warning' => 72, // Warning before close date
    ];

    /**
     * @param array<string, int> $thresholds Custom SLA thresholds in hours
     */
    public function __construct(
        private array $thresholds = self::DEFAULT_SLA_THRESHOLDS
    ) {}

    /**
     * Evaluate lead SLA status
     * 
     * @param LeadInterface $lead Lead to evaluate
     * @param array<string, mixed> $context Additional context
     * @return SLAResult SLA evaluation result
     */
    public function evaluateLead(LeadInterface $lead, array $context = []): SLAResult
    {
        $breaches = [];
        $warnings = [];
        $now = new \DateTimeImmutable();

        // Check first contact SLA
        $firstContactHours = $this->thresholds['lead_first_contact'] ?? 24;
        $firstContactDeadline = $lead->getCreatedAt()->modify("+{$firstContactHours} hours");
        
        if ($lead->getStatus()->value === 'new' && $now > $firstContactDeadline) {
            $breaches[] = [
                'type' => 'first_contact',
                'message' => sprintf(
                    'Lead not contacted within %d hours SLA',
                    $firstContactHours
                ),
                'severity' => 'high',
                'deadline' => $firstContactDeadline->format('c'),
            ];
        } elseif ($lead->getStatus()->value === 'new') {
            $hoursRemaining = ($firstContactDeadline->getTimestamp() - $now->getTimestamp()) / 3600;
            if ($hoursRemaining <= 4) {
                $warnings[] = [
                    'type' => 'first_contact',
                    'message' => sprintf(
                        'First contact SLA deadline in %.1f hours',
                        $hoursRemaining
                    ),
                    'deadline' => $firstContactDeadline->format('c'),
                ];
            }
        }

        // Check qualification SLA
        $qualificationHours = $this->thresholds['lead_qualification'] ?? 72;
        $qualificationDeadline = $lead->getCreatedAt()->modify("+{$qualificationHours} hours");
        
        if ($lead->getStatus()->isActive() && !$lead->isQualified() && $now > $qualificationDeadline) {
            $breaches[] = [
                'type' => 'qualification',
                'message' => sprintf(
                    'Lead not qualified within %d hours SLA',
                    $qualificationHours
                ),
                'severity' => 'medium',
                'deadline' => $qualificationDeadline->format('c'),
            ];
        }

        return new SLAResult(
            hasBreaches: !empty($breaches),
            hasWarnings: !empty($warnings),
            breaches: $breaches,
            warnings: $warnings,
            entityId: $lead->getId(),
            entityType: 'lead'
        );
    }

    /**
     * Evaluate opportunity SLA status
     * 
     * @param OpportunityInterface $opportunity Opportunity to evaluate
     * @param array<string, mixed> $context Additional context
     * @return SLAResult SLA evaluation result
     */
    public function evaluateOpportunity(
        OpportunityInterface $opportunity,
        array $context = []
    ): SLAResult {
        $breaches = [];
        $warnings = [];
        $now = new \DateTimeImmutable();

        if (!$opportunity->isOpen()) {
            return new SLAResult(
                hasBreaches: false,
                hasWarnings: false,
                breaches: [],
                warnings: [],
                entityId: $opportunity->getId(),
                entityType: 'opportunity'
            );
        }

        // Check follow-up SLA
        $lastActivityAt = $context['last_activity_at'] ?? null;
        $followUpHours = $this->thresholds['opportunity_follow_up'] ?? 48;
        
        if ($lastActivityAt !== null) {
            $lastActivity = new \DateTimeImmutable($lastActivityAt);
            $followUpDeadline = $lastActivity->modify("+{$followUpHours} hours");
            
            if ($now > $followUpDeadline) {
                $breaches[] = [
                    'type' => 'follow_up',
                    'message' => sprintf(
                        'No follow-up within %d hours SLA',
                        $followUpHours
                    ),
                    'severity' => 'medium',
                    'deadline' => $followUpDeadline->format('c'),
                ];
            }
        }

        // Check close date warning
        $closeWarningHours = $this->thresholds['opportunity_close_warning'] ?? 72;
        $expectedCloseDate = $opportunity->getExpectedCloseDate();
        $closeWarningDate = $expectedCloseDate->modify("-{$closeWarningHours} hours");
        
        if ($now >= $closeWarningDate && $now <= $expectedCloseDate) {
            $hoursRemaining = ($expectedCloseDate->getTimestamp() - $now->getTimestamp()) / 3600;
            $warnings[] = [
                'type' => 'close_date',
                'message' => sprintf(
                    'Expected close date in %.1f hours',
                    $hoursRemaining
                ),
                'deadline' => $expectedCloseDate->format('c'),
            ];
        } elseif ($now > $expectedCloseDate) {
            $breaches[] = [
                'type' => 'close_date',
                'message' => 'Expected close date has passed',
                'severity' => 'high',
                'deadline' => $expectedCloseDate->format('c'),
            ];
        }

        // Check stale opportunity
        $daysInStage = $opportunity->getDaysInCurrentStage();
        if ($daysInStage > 14) {
            $warnings[] = [
                'type' => 'stale',
                'message' => sprintf(
                    'Opportunity in current stage for %d days',
                    $daysInStage
                ),
                'deadline' => null,
            ];
        }

        return new SLAResult(
            hasBreaches: !empty($breaches),
            hasWarnings: !empty($warnings),
            breaches: $breaches,
            warnings: $warnings,
            entityId: $opportunity->getId(),
            entityType: 'opportunity'
        );
    }

    /**
     * Get SLA thresholds
     * 
     * @return array<string, int>
     */
    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    /**
     * Check if lead has SLA breach
     */
    public function hasLeadBreach(LeadInterface $lead, array $context = []): bool
    {
        return $this->evaluateLead($lead, $context)->hasBreaches;
    }

    /**
     * Check if opportunity has SLA breach
     */
    public function hasOpportunityBreach(
        OpportunityInterface $opportunity,
        array $context = []
    ): bool {
        return $this->evaluateOpportunity($opportunity, $context)->hasBreaches;
    }
}

/**
 * SLA Result DTO
 */
final readonly class SLAResult
{
    /**
     * @param bool $hasBreaches Whether there are SLA breaches
     * @param bool $hasWarnings Whether there are SLA warnings
     * @param array<string, mixed> $breaches List of breaches
     * @param array<string, mixed> $warnings List of warnings
     * @param string $entityId Entity ID
     * @param string $entityType Entity type (lead/opportunity)
     */
    public function __construct(
        public bool $hasBreaches,
        public bool $hasWarnings,
        public array $breaches,
        public array $warnings,
        public string $entityId,
        public string $entityType
    ) {}
}