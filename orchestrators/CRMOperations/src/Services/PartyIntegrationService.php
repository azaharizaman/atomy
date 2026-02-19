<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\CustomerProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Party Integration Service
 * 
 * Provides integration with the Party package for customer data.
 * Implements the CustomerProviderInterface.
 * 
 * @package Nexus\CRMOperations\Services
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class PartyIntegrationService implements CustomerProviderInterface
{
    /**
     * @param object $partyService Party package service (injected via container)
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private object $partyService,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * @inheritDoc
     */
    public function findById(string $id): ?array
    {
        $this->logger?->debug('Finding customer by ID', ['customer_id' => $id]);

        // In real implementation, this would call the Party package
        // return $this->partyService->findCustomerById($id);
        
        return null;
    }

    /**
     * @inheritDoc
     */
    public function findByEmail(string $email): ?array
    {
        $this->logger?->debug('Finding customer by email', ['email' => $email]);

        // In real implementation, this would call the Party package
        // return $this->partyService->findCustomerByEmail($email);
        
        return null;
    }

    /**
     * @inheritDoc
     */
    public function createFromLead(array $leadData): string
    {
        $this->logger?->info('Creating customer from lead', [
            'lead_id' => $leadData['id'] ?? null,
        ]);

        // In real implementation, this would call the Party package
        // $customer = $this->partyService->createCustomer([
        //     'name' => $leadData['title'],
        //     'source' => $leadData['source'],
        //     ...
        // ]);
        // return $customer->getId();

        return sprintf('CUS-%s', uniqid());
    }

    /**
     * @inheritDoc
     */
    public function update(string $customerId, array $data): void
    {
        $this->logger?->info('Updating customer', [
            'customer_id' => $customerId,
        ]);

        // In real implementation, this would call the Party package
        // $this->partyService->updateCustomer($customerId, $data);
    }

    /**
     * @inheritDoc
     */
    public function exists(string $customerId): bool
    {
        return $this->findById($customerId) !== null;
    }

    /**
     * @inheritDoc
     */
    public function linkToOpportunity(string $customerId, string $opportunityId): void
    {
        $this->logger?->info('Linking customer to opportunity', [
            'customer_id' => $customerId,
            'opportunity_id' => $opportunityId,
        ]);

        // In real implementation, this would update the customer record
        // to link it with the opportunity
    }

    /**
     * @inheritDoc
     */
    public function getCommunicationPreferences(string $customerId): array
    {
        $this->logger?->debug('Getting communication preferences', [
            'customer_id' => $customerId,
        ]);

        // In real implementation, this would retrieve from Party package
        return [
            'email' => true,
            'sms' => false,
            'phone' => true,
        ];
    }
}