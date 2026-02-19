<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Coordinators;

use Nexus\CRM\Contracts\LeadInterface;
use Nexus\CRM\Contracts\LeadQueryInterface;
use Nexus\CRM\Contracts\LeadPersistInterface;
use Nexus\CRM\Contracts\OpportunityPersistInterface;
use Nexus\CRM\Enums\LeadStatus;
use Nexus\CRM\Enums\OpportunityStage;
use Nexus\CRMOperations\Contracts\CustomerProviderInterface;
use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Nexus\CRMOperations\Contracts\AnalyticsProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Lead Conversion Coordinator
 * 
 * Orchestrates the lead to opportunity conversion process.
 * Coordinates between CRM, Party, and Notification packages.
 * 
 * @package Nexus\CRMOperations\Coordinators
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class LeadConversionCoordinator
{
    /**
     * @param LeadQueryInterface $leadQuery Lead query service
     * @param LeadPersistInterface $leadPersist Lead persistence service
     * @param OpportunityPersistInterface $opportunityPersist Opportunity persistence service
     * @param CustomerProviderInterface $customerProvider Customer data provider
     * @param NotificationProviderInterface $notificationProvider Notification provider
     * @param AnalyticsProviderInterface $analyticsProvider Analytics provider
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
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Convert a lead to an opportunity
     * 
     * @param string $leadId Lead ID to convert
     * @param array<string, mixed> $options Conversion options
     * @return ConversionResult Conversion result with opportunity and customer IDs
     * @throws \InvalidArgumentException If lead cannot be converted
     */
    public function convertLead(string $leadId, array $options = []): ConversionResult
    {
        // Load the lead
        $lead = $this->leadQuery->findByIdOrFail($leadId);

        // Validate lead can be converted
        $this->validateLeadForConversion($lead);

        $this->logger?->info('Starting lead conversion', [
            'lead_id' => $leadId,
            'lead_status' => $lead->getStatus()->value,
        ]);

        // Create or find customer
        $customerId = $this->createOrFindCustomer($lead, $options);

        // Create opportunity
        $opportunityId = $this->createOpportunity($lead, $options);

        // Link customer to opportunity
        $this->customerProvider->linkToOpportunity($customerId, $opportunityId);

        // Mark lead as converted
        $this->leadPersist->updateStatus($leadId, LeadStatus::Converted);

        // Track analytics
        $this->analyticsProvider->trackConversion('lead', $leadId, [
            'opportunity_id' => $opportunityId,
            'customer_id' => $customerId,
            'source' => $lead->getSource()->value,
        ]);

        // Send notifications
        $this->sendConversionNotifications($lead, $opportunityId);

        $this->logger?->info('Lead conversion completed', [
            'lead_id' => $leadId,
            'opportunity_id' => $opportunityId,
            'customer_id' => $customerId,
        ]);

        return new ConversionResult(
            leadId: $leadId,
            opportunityId: $opportunityId,
            customerId: $customerId
        );
    }

    /**
     * Validate lead can be converted
     */
    private function validateLeadForConversion(LeadInterface $lead): void
    {
        if (!$lead->isConvertible()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Lead %s cannot be converted. Current status: %s',
                    $lead->getId(),
                    $lead->getStatus()->label()
                )
            );
        }
    }

    /**
     * Create or find customer from lead
     */
    private function createOrFindCustomer(LeadInterface $lead, array $options): string
    {
        // Check if customer already exists
        $existingCustomerId = $options['customer_id'] ?? null;
        
        if ($existingCustomerId && $this->customerProvider->exists($existingCustomerId)) {
            return $existingCustomerId;
        }

        // Create new customer from lead data
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
     * Create opportunity from lead
     */
    private function createOpportunity(LeadInterface $lead, array $options): string
    {
        $pipelineId = $options['pipeline_id'] ?? throw new \InvalidArgumentException(
            'Pipeline ID is required for opportunity creation'
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
     * Send conversion notifications
     */
    private function sendConversionNotifications(
        LeadInterface $lead,
        string $opportunityId
    ): void {
        $this->notificationProvider->notifyRole(
            'sales_manager',
            'Lead Converted to Opportunity',
            sprintf(
                'Lead "%s" has been converted to opportunity %s',
                $lead->getTitle(),
                $opportunityId
            ),
            [
                'lead_id' => $lead->getId(),
                'opportunity_id' => $opportunityId,
            ]
        );
    }
}

/**
 * Conversion Result DTO
 */
final readonly class ConversionResult
{
    public function __construct(
        public string $leadId,
        public string $opportunityId,
        public string $customerId
    ) {}
}
