<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Workflows;

use Nexus\CRM\Contracts\LeadInterface;
use Nexus\CRM\Contracts\LeadQueryInterface;
use Nexus\CRM\Contracts\OpportunityInterface;
use Nexus\CRM\Contracts\OpportunityQueryInterface;
use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Nexus\CRMOperations\Contracts\AnalyticsProviderInterface;
use Nexus\CRMOperations\Rules\SLABreachRule;
use Nexus\CRMOperations\DataProviders\LeadContextDataProvider;
use Nexus\CRMOperations\DataProviders\OpportunityContextDataProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Escalation Workflow
 * 
 * Handles SLA breach detection and escalation processes.
 * Monitors leads and opportunities for SLA violations.
 * 
 * @package Nexus\CRMOperations\Workflows
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class EscalationWorkflow
{
    /**
     * @param LeadQueryInterface $leadQuery Lead query service
     * @param OpportunityQueryInterface $opportunityQuery Opportunity query service
     * @param NotificationProviderInterface $notificationProvider Notification provider
     * @param AnalyticsProviderInterface $analyticsProvider Analytics provider
     * @param SLABreachRule $slaRule SLA breach rule
     * @param LeadContextDataProvider $leadContextProvider Lead context provider
     * @param OpportunityContextDataProvider $opportunityContextProvider Opportunity context provider
     * @param EventDispatcherInterface $eventDispatcher Event dispatcher
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private LeadQueryInterface $leadQuery,
        private OpportunityQueryInterface $opportunityQuery,
        private NotificationProviderInterface $notificationProvider,
        private AnalyticsProviderInterface $analyticsProvider,
        private SLABreachRule $slaRule,
        private LeadContextDataProvider $leadContextProvider,
        private OpportunityContextDataProvider $opportunityContextProvider,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Check and escalate SLA breaches for leads
     * 
     * @param string $tenantId Tenant ID
     * @return EscalationWorkflowResult Workflow result
     */
    public function checkLeadSLABreaches(string $tenantId): EscalationWorkflowResult
    {
        $this->logger?->info('Checking lead SLA breaches', ['tenant_id' => $tenantId]);

        $breaches = [];
        $warnings = [];

        // Check all active leads
        foreach ($this->leadQuery->findByStatus(\Nexus\CRM\Enums\LeadStatus::New) as $lead) {
            $context = $this->leadContextProvider->getContext($lead->getId());
            $result = $this->slaRule->evaluateLead($lead, $context);

            if ($result->hasBreaches) {
                $breaches[] = [
                    'entity_id' => $lead->getId(),
                    'entity_type' => 'lead',
                    'breaches' => $result->breaches,
                ];

                $this->escalateLead($lead, $result);
            }

            if ($result->hasWarnings) {
                $warnings[] = [
                    'entity_id' => $lead->getId(),
                    'entity_type' => 'lead',
                    'warnings' => $result->warnings,
                ];
            }
        }

        $this->analyticsProvider->track('sla_check_completed', [
            'entity_type' => 'lead',
            'breach_count' => count($breaches),
            'warning_count' => count($warnings),
        ]);

        return new EscalationWorkflowResult(
            success: true,
            breachesFound: count($breaches),
            warningsFound: count($warnings),
            breaches: $breaches,
            warnings: $warnings
        );
    }

    /**
     * Check and escalate SLA breaches for opportunities
     * 
     * @param string $tenantId Tenant ID
     * @return EscalationWorkflowResult Workflow result
     */
    public function checkOpportunitySLABreaches(string $tenantId): EscalationWorkflowResult
    {
        $this->logger?->info('Checking opportunity SLA breaches', ['tenant_id' => $tenantId]);

        $breaches = [];
        $warnings = [];

        // Check all open opportunities
        foreach ($this->opportunityQuery->findOpen() as $opportunity) {
            $context = $this->opportunityContextProvider->getContext($opportunity->getId());
            $result = $this->slaRule->evaluateOpportunity($opportunity, $context);

            if ($result->hasBreaches) {
                $breaches[] = [
                    'entity_id' => $opportunity->getId(),
                    'entity_type' => 'opportunity',
                    'breaches' => $result->breaches,
                ];

                $this->escalateOpportunity($opportunity, $result);
            }

            if ($result->hasWarnings) {
                $warnings[] = [
                    'entity_id' => $opportunity->getId(),
                    'entity_type' => 'opportunity',
                    'warnings' => $result->warnings,
                ];
            }
        }

        $this->analyticsProvider->track('sla_check_completed', [
            'entity_type' => 'opportunity',
            'breach_count' => count($breaches),
            'warning_count' => count($warnings),
        ]);

        return new EscalationWorkflowResult(
            success: true,
            breachesFound: count($breaches),
            warningsFound: count($warnings),
            breaches: $breaches,
            warnings: $warnings
        );
    }

    /**
     * Escalate a specific lead
     * 
     * @param string $leadId Lead ID
     * @return EscalationWorkflowResult Workflow result
     */
    public function escalateLeadById(string $leadId): EscalationWorkflowResult
    {
        $lead = $this->leadQuery->findById($leadId);
        
        if ($lead === null) {
            return new EscalationWorkflowResult(
                success: false,
                breachesFound: 0,
                warningsFound: 0,
                breaches: [],
                warnings: []
            );
        }

        $context = $this->leadContextProvider->getContext($leadId);
        $result = $this->slaRule->evaluateLead($lead, $context);

        if ($result->hasBreaches) {
            $this->escalateLead($lead, $result);
        }

        return new EscalationWorkflowResult(
            success: true,
            breachesFound: $result->hasBreaches ? 1 : 0,
            warningsFound: $result->hasWarnings ? 1 : 0,
            breaches: $result->breaches,
            warnings: $result->warnings
        );
    }

    /**
     * Escalate a specific opportunity
     * 
     * @param string $opportunityId Opportunity ID
     * @return EscalationWorkflowResult Workflow result
     */
    public function escalateOpportunityById(string $opportunityId): EscalationWorkflowResult
    {
        $opportunity = $this->opportunityQuery->findById($opportunityId);
        
        if ($opportunity === null) {
            return new EscalationWorkflowResult(
                success: false,
                breachesFound: 0,
                warningsFound: 0,
                breaches: [],
                warnings: []
            );
        }

        $context = $this->opportunityContextProvider->getContext($opportunityId);
        $result = $this->slaRule->evaluateOpportunity($opportunity, $context);

        if ($result->hasBreaches) {
            $this->escalateOpportunity($opportunity, $result);
        }

        return new EscalationWorkflowResult(
            success: true,
            breachesFound: $result->hasBreaches ? 1 : 0,
            warningsFound: $result->hasWarnings ? 1 : 0,
            breaches: $result->breaches,
            warnings: $result->warnings
        );
    }

    /**
     * Escalate lead SLA breach
     */
    private function escalateLead(LeadInterface $lead, $result): void
    {
        $this->logger?->warning('Escalating lead SLA breach', [
            'lead_id' => $lead->getId(),
            'breaches' => $result->breaches,
        ]);

        // Send escalation notification
        $this->notificationProvider->escalate(
            'manager',
            'Lead SLA Breach Detected',
            sprintf(
                'Lead "%s" has SLA breaches that require attention',
                $lead->getTitle()
            ),
            [
                'lead_id' => $lead->getId(),
                'breaches' => $result->breaches,
            ]
        );

        // Track escalation
        $this->analyticsProvider->track('sla_escalation', [
            'entity_id' => $lead->getId(),
            'entity_type' => 'lead',
            'breach_count' => count($result->breaches),
        ]);
    }

    /**
     * Escalate opportunity SLA breach
     */
    private function escalateOpportunity(OpportunityInterface $opportunity, $result): void
    {
        $this->logger?->warning('Escalating opportunity SLA breach', [
            'opportunity_id' => $opportunity->getId(),
            'breaches' => $result->breaches,
        ]);

        // Send escalation notification
        $this->notificationProvider->escalate(
            'manager',
            'Opportunity SLA Breach Detected',
            sprintf(
                'Opportunity "%s" has SLA breaches that require attention',
                $opportunity->getTitle()
            ),
            [
                'opportunity_id' => $opportunity->getId(),
                'breaches' => $result->breaches,
            ]
        );

        // Track escalation
        $this->analyticsProvider->track('sla_escalation', [
            'entity_id' => $opportunity->getId(),
            'entity_type' => 'opportunity',
            'breach_count' => count($result->breaches),
        ]);
    }
}

/**
 * Escalation Workflow Result DTO
 */
final readonly class EscalationWorkflowResult
{
    /**
     * @param bool $success Whether workflow succeeded
     * @param int $breachesFound Number of breaches found
     * @param int $warningsFound Number of warnings found
     * @param array<string, mixed> $breaches List of breaches
     * @param array<string, mixed> $warnings List of warnings
     */
    public function __construct(
        public bool $success,
        public int $breachesFound,
        public int $warningsFound,
        public array $breaches,
        public array $warnings
    ) {}
}