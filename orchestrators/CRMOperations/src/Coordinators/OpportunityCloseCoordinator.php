<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Coordinators;

use Nexus\CRM\Contracts\OpportunityInterface;
use Nexus\CRM\Contracts\OpportunityQueryInterface;
use Nexus\CRM\Contracts\OpportunityPersistInterface;
use Nexus\CRM\Enums\OpportunityStage;
use Nexus\CRMOperations\Contracts\CustomerProviderInterface;
use Nexus\CRMOperations\Contracts\QuotationProviderInterface;
use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Nexus\CRMOperations\Contracts\AnalyticsProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Opportunity Close Coordinator
 * 
 * Orchestrates the opportunity closing process.
 * Coordinates between CRM, Sales, Party, and Notification packages.
 * 
 * @package Nexus\CRMOperations\Coordinators
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class OpportunityCloseCoordinator
{
    /**
     * @param OpportunityQueryInterface $opportunityQuery Opportunity query service
     * @param OpportunityPersistInterface $opportunityPersist Opportunity persistence service
     * @param QuotationProviderInterface $quotationProvider Quotation provider
     * @param CustomerProviderInterface $customerProvider Customer provider
     * @param NotificationProviderInterface $notificationProvider Notification provider
     * @param AnalyticsProviderInterface $analyticsProvider Analytics provider
     * @param EventDispatcherInterface $eventDispatcher Event dispatcher
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private OpportunityQueryInterface $opportunityQuery,
        private OpportunityPersistInterface $opportunityPersist,
        private QuotationProviderInterface $quotationProvider,
        private CustomerProviderInterface $customerProvider,
        private NotificationProviderInterface $notificationProvider,
        private AnalyticsProviderInterface $analyticsProvider,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Close opportunity as won
     * 
     * @param string $opportunityId Opportunity ID
     * @param array<string, mixed> $options Close options
     * @return CloseResult Close result
     */
    public function closeAsWon(string $opportunityId, array $options = []): CloseResult
    {
        $opportunity = $this->opportunityQuery->findByIdOrFail($opportunityId);

        $this->logger?->info('Closing opportunity as won', [
            'opportunity_id' => $opportunityId,
            'current_stage' => $opportunity->getStage()->value,
        ]);

        // Get or create quotation
        $quotationId = $this->getOrCreateQuotation($opportunity, $options);

        // Mark quotation as accepted
        $this->quotationProvider->markAsAccepted($quotationId);

        // Close the opportunity
        $actualValue = $options['actual_value'] ?? $opportunity->getValue();
        $this->opportunityPersist->markAsWon($opportunityId, $actualValue);

        // Track analytics
        $this->analyticsProvider->trackConversion('opportunity', $opportunityId, [
            'type' => 'won',
            'value' => $actualValue,
            'currency' => $opportunity->getCurrency(),
            'quotation_id' => $quotationId,
        ]);

        // Send notifications
        $this->sendWonNotifications($opportunity, $actualValue);

        $this->logger?->info('Opportunity closed as won', [
            'opportunity_id' => $opportunityId,
            'actual_value' => $actualValue,
            'quotation_id' => $quotationId,
        ]);

        return new CloseResult(
            opportunityId: $opportunityId,
            status: 'won',
            actualValue: $actualValue,
            quotationId: $quotationId
        );
    }

    /**
     * Close opportunity as lost
     * 
     * @param string $opportunityId Opportunity ID
     * @param string $reason Loss reason
     * @param array<string, mixed> $options Close options
     * @return CloseResult Close result
     */
    public function closeAsLost(
        string $opportunityId,
        string $reason,
        array $options = []
    ): CloseResult {
        $opportunity = $this->opportunityQuery->findByIdOrFail($opportunityId);

        $this->logger?->info('Closing opportunity as lost', [
            'opportunity_id' => $opportunityId,
            'reason' => $reason,
        ]);

        // Close the opportunity
        $this->opportunityPersist->markAsLost($opportunityId, $reason);

        // Mark any existing quotation as rejected
        $quotation = $this->quotationProvider->findByOpportunityId($opportunityId);
        if ($quotation !== null) {
            $this->quotationProvider->markAsRejected(
                $quotation['id'],
                $reason
            );
        }

        // Track analytics
        $this->analyticsProvider->trackConversion('opportunity', $opportunityId, [
            'type' => 'lost',
            'reason' => $reason,
            'value' => $opportunity->getValue(),
            'currency' => $opportunity->getCurrency(),
        ]);

        // Send notifications
        $this->sendLostNotifications($opportunity, $reason);

        $this->logger?->info('Opportunity closed as lost', [
            'opportunity_id' => $opportunityId,
            'reason' => $reason,
        ]);

        return new CloseResult(
            opportunityId: $opportunityId,
            status: 'lost',
            actualValue: 0,
            quotationId: null,
            lossReason: $reason
        );
    }

    /**
     * Get or create quotation for opportunity
     */
    private function getOrCreateQuotation(
        OpportunityInterface $opportunity,
        array $options
    ): string {
        // Check for existing quotation
        $existingQuotation = $this->quotationProvider->findByOpportunityId(
            $opportunity->getId()
        );

        if ($existingQuotation !== null) {
            return $existingQuotation['id'];
        }

        // Create new quotation
        return $this->quotationProvider->createFromOpportunity([
            'id' => $opportunity->getId(),
            'title' => $opportunity->getTitle(),
            'value' => $opportunity->getValue(),
            'currency' => $opportunity->getCurrency(),
            'customer_id' => $options['customer_id'] ?? null,
        ]);
    }

    /**
     * Send won notifications
     */
    private function sendWonNotifications(
        OpportunityInterface $opportunity,
        int $actualValue
    ): void {
        $this->notificationProvider->notifyRole(
            'sales_manager',
            'Deal Won!',
            sprintf(
                'Opportunity "%s" has been closed as won for %s',
                $opportunity->getTitle(),
                $this->formatValue($actualValue, $opportunity->getCurrency())
            ),
            [
                'opportunity_id' => $opportunity->getId(),
                'value' => $actualValue,
                'currency' => $opportunity->getCurrency(),
            ]
        );
    }

    /**
     * Send lost notifications
     */
    private function sendLostNotifications(
        OpportunityInterface $opportunity,
        string $reason
    ): void {
        $this->notificationProvider->notifyRole(
            'sales_manager',
            'Deal Lost',
            sprintf(
                'Opportunity "%s" has been closed as lost. Reason: %s',
                $opportunity->getTitle(),
                $reason
            ),
            [
                'opportunity_id' => $opportunity->getId(),
                'reason' => $reason,
            ]
        );
    }

    /**
     * Format value for display
     */
    private function formatValue(int $value, string $currency): string
    {
        return sprintf('%s %s', number_format($value / 100, 2), $currency);
    }
}

/**
 * Close Result DTO
 */
final readonly class CloseResult
{
    public function __construct(
        public string $opportunityId,
        public string $status,
        public int $actualValue,
        public ?string $quotationId,
        public ?string $lossReason = null
    ) {}
}
