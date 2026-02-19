<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Coordinators;

use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Psr\Log\LoggerInterface;

final readonly class PartnerSalesCoordinator
{
    public function __construct(
        private NotificationProviderInterface $notificationProvider,
        private ?LoggerInterface $logger = null
    ) {}

    public function distributeLeadsToPartners(array $leads, array $partners): array
    {
        $results = [];
        
        foreach ($leads as $lead) {
            $results[$lead['id']] = $this->assignPartner($lead, $partners);
        }
        
        return $results;
    }

    public function registerPartnerOpportunity(string $partnerId, string $opportunityId, array $dealDetails): string
    {
        $registrationId = uniqid('reg_');
        
        $this->logger?->info('Partner opportunity registered', [
            'partner_id' => $partnerId,
            'opportunity_id' => $opportunityId,
            'registration_id' => $registrationId
        ]);
        
        return $registrationId;
    }

    public function calculatePartnerCommission(string $partnerId, string $opportunityId): int
    {
        return 5000;
    }

    public function handleDealRegistration(string $partnerId, array $dealInfo): array
    {
        return [
            'partner_id' => $partnerId,
            'deal_id' => $dealInfo['id'],
            'status' => 'pending_approval',
            'protection_expiry' => (new \DateTimeImmutable())->modify('+30 days')->format('Y-m-d')
        ];
    }

    public function validatePartnerExclusivity(string $partnerId, string $customerId): bool
    {
        return true;
    }

    private function assignPartner(array $lead, array $partners): ?string
    {
        if (empty($partners)) {
            return null;
        }
        
        return $partners[0]['id'] ?? null;
    }
}
