<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Workflows;

use Nexus\CRM\Contracts\LeadInterface;
use Nexus\CRM\Contracts\LeadQueryInterface;
use Nexus\CRM\Contracts\LeadPersistInterface;
use Nexus\CRM\Contracts\OpportunityPersistInterface;
use Nexus\CRM\Enums\LeadStatus;
use Nexus\CRMOperations\Contracts\CustomerProviderInterface;
use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Nexus\CRMOperations\Contracts\AnalyticsProviderInterface;
use Nexus\CRMOperations\Rules\LeadQualificationRule;
use Nexus\CRMOperations\DataProviders\LeadContextDataProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Lead to Opportunity Workflow
 * 
 * Multi-step workflow for converting leads to opportunities.
 * Handles qualification, customer creation, and opportunity creation.
 * 
 * @package Nexus\CRMOperations\Workflows
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class LeadToOpportunityWorkflow
{
    /**
     * @param LeadQueryInterface $leadQuery Lead query service
     * @param LeadPersistInterface $leadPersist Lead persistence service
     * @param OpportunityPersistInterface $opportunityPersist Opportunity persistence service
     * @param CustomerProviderInterface $customerProvider Customer provider
     * @param NotificationProviderInterface $notificationProvider Notification provider
     * @param AnalyticsProviderInterface $analyticsProvider Analytics provider
     * @param LeadQualificationRule $qualificationRule Qualification rule
     * @param LeadContextDataProvider $contextProvider Context data provider
     * @param EventDispatcherInterface $eventDispatcher Event dispatcher
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private LeadQueryInterface $leadQuery,
        private LeadPersistInterface $leadPersist,
        private OpportunityPersistInterface $opportunityPersist,
        private CustomerProviderInterface $customerProvider,
        private NotificationProviderInterface $notificationProvider,
        private AnalyticsProviderInterface $analyticsProvider,
        private LeadQualificationRule $qualificationRule,
        private LeadContextDataProvider $contextProvider,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Execute the lead to opportunity workflow
     * 
     * @param string $leadId Lead ID
     * @param array<string, mixed> $options Workflow options
     * @return WorkflowResult Workflow result
     */
    public function execute(string $leadId, array $options = []): WorkflowResult
    {
        $this->logger?->info('Starting Lead to Opportunity workflow', [
            'lead_id' => $leadId,
        ]);

        $steps = [];
        $completed = false;
        $opportunityId = null;
        $customerId = null;

        try {
            // Step 1: Load and validate lead
            $lead = $this->stepLoadLead($leadId);
            $steps[] = ['name' => 'load_lead', 'status' => 'completed'];

            // Step 2: Check qualification
            $qualificationResult = $this->stepCheckQualification($lead);
            $steps[] = ['name' => 'check_qualification', 'status' => 'completed'];

            if (!$qualificationResult->qualified) {
                return new WorkflowResult(
                    success: false,
                    steps: $steps,
                    opportunityId: null,
                    customerId: null,
                    errors: $qualificationResult->reasons
                );
            }

            // Step 3: Create or find customer
            $customerId = $this->stepCreateCustomer($lead, $options);
            $steps[] = ['name' => 'create_customer', 'status' => 'completed'];

            // Step 4: Create opportunity
            $opportunityId = $this->stepCreateOpportunity($lead, $options);
            $steps[] = ['name' => 'create_opportunity', 'status' => 'completed'];

            // Step 5: Link customer to opportunity
            $this->stepLinkCustomer($customerId, $opportunityId);
            $steps[] = ['name' => 'link_customer', 'status' => 'completed'];

            // Step 6: Update lead status
            $this->stepUpdateLeadStatus($leadId);
            $steps[] = ['name' => 'update_lead_status', 'status' => 'completed'];

            // Step 7: Send notifications
            $this->stepSendNotifications($lead, $opportunityId);
            $steps[] = ['name' => 'send_notifications', 'status' => 'completed'];

            // Step 8: Track analytics
            $this->stepTrackAnalytics($lead, $opportunityId, $customerId);
            $steps[] = ['name' => 'track_analytics', 'status' => 'completed'];

            $completed = true;

        } catch (\Throwable $e) {
            $steps[] = ['name' => 'error', 'status' => 'failed', 'message' => $e->getMessage()];
            
            $this->logger?->error('Lead to Opportunity workflow failed', [
                'lead_id' => $leadId,
                'error' => $e->getMessage(),
            ]);

            return new WorkflowResult(
                success: false,
                steps: $steps,
                opportunityId: $opportunityId,
                customerId: $customerId,
                errors: [$e->getMessage()]
            );
        }

        $this->logger?->info('Lead to Opportunity workflow completed', [
            'lead_id' => $leadId,
            'opportunity_id' => $opportunityId,
            'customer_id' => $customerId,
        ]);

        return new WorkflowResult(
            success: true,
            steps: $steps,
            opportunityId: $opportunityId,
            customerId: $customerId,
            errors: []
        );
    }

    /**
     * Step 1: Load and validate lead
     */
    private function stepLoadLead(string $leadId): LeadInterface
    {
        $lead = $this->leadQuery->findByIdOrFail($leadId);

        if (!$lead->isConvertible()) {
            throw new \InvalidArgumentException(
                sprintf('Lead %s is not convertible', $leadId)
            );
        }

        return $lead;
    }

    /**
     * Step 2: Check qualification
     */
    private function stepCheckQualification(LeadInterface $lead): \Nexus\CRMOperations\Rules\QualificationResult
    {
        $context = $this->contextProvider->getContext($lead->getId());
        return $this->qualificationRule->evaluate($lead, $context);
    }

    /**
     * Step 3: Create or find customer
     */
    private function stepCreateCustomer(LeadInterface $lead, array $options): string
    {
        $existingCustomerId = $options['customer_id'] ?? null;
        
        if ($existingCustomerId && $this->customerProvider->exists($existingCustomerId)) {
            return $existingCustomerId;
        }

        return $this->customerProvider->createFromLead([
            'id' => $lead->getId(),
            'title' => $lead->getTitle(),
            'description' => $lead->getDescription(),
            'estimated_value' => $lead->getEstimatedValue(),
            'currency' => $lead->getCurrency(),
            'source' => $lead->getSource()->value,
        ]);
    }

    /**
     * Step 4: Create opportunity
     */
    private function stepCreateOpportunity(LeadInterface $lead, array $options): string
    {
        $pipelineId = $options['pipeline_id'] ?? throw new \InvalidArgumentException(
            'Pipeline ID is required'
        );

        $opportunity = $this->opportunityPersist->create(
            tenantId: $lead->getTenantId(),
            pipelineId: $pipelineId,
            title: $lead->getTitle(),
            value: $lead->getEstimatedValue() ?? 0,
            currency: $lead->getCurrency() ?? 'USD',
            expectedCloseDate: $options['expected_close_date'] 
                ?? new \DateTimeImmutable('+30 days'),
            description: $lead->getDescription(),
            sourceLeadId: $lead->getId()
        );

        return $opportunity->getId();
    }

    /**
     * Step 5: Link customer to opportunity
     */
    private function stepLinkCustomer(string $customerId, string $opportunityId): void
    {
        $this->customerProvider->linkToOpportunity($customerId, $opportunityId);
    }

    /**
     * Step 6: Update lead status
     */
    private function stepUpdateLeadStatus(string $leadId): void
    {
        $this->leadPersist->updateStatus($leadId, LeadStatus::Converted);
    }

    /**
     * Step 7: Send notifications
     */
    private function stepSendNotifications(LeadInterface $lead, string $opportunityId): void
    {
        $this->notificationProvider->notifyRole(
            'sales_manager',
            'Lead Converted to Opportunity',
            sprintf('Lead "%s" has been converted', $lead->getTitle()),
            ['lead_id' => $lead->getId(), 'opportunity_id' => $opportunityId]
        );
    }

    /**
     * Step 8: Track analytics
     */
    private function stepTrackAnalytics(
        LeadInterface $lead,
        string $opportunityId,
        string $customerId
    ): void {
        $this->analyticsProvider->trackConversion('lead', $lead->getId(), [
            'opportunity_id' => $opportunityId,
            'customer_id' => $customerId,
            'source' => $lead->getSource()->value,
        ]);
    }
}

/**
 * Workflow Result DTO
 */
final readonly class WorkflowResult
{
    /**
     * @param bool $success Whether workflow succeeded
     * @param array<string, mixed> $steps Completed steps
     * @param string|null $opportunityId Created opportunity ID
     * @param string|null $customerId Created or linked customer ID
     * @param array<string> $errors Error messages
     */
    public function __construct(
        public bool $success,
        public array $steps,
        public ?string $opportunityId,
        public ?string $customerId,
        public array $errors
    ) {}
}