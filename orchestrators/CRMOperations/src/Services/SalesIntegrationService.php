<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\QuotationProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Sales Integration Service
 * 
 * Provides integration with the Sales package for quotation data.
 * Implements the QuotationProviderInterface.
 * 
 * @package Nexus\CRMOperations\Services
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class SalesIntegrationService implements QuotationProviderInterface
{
    /**
     * @param object $salesService Sales package service (injected via container)
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private object $salesService,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * @inheritDoc
     */
    public function findById(string $id): ?array
    {
        $this->logger?->debug('Finding quotation by ID', ['quotation_id' => $id]);

        // In real implementation, this would call the Sales package
        // return $this->salesService->findQuotationById($id);
        
        return null;
    }

    /**
     * @inheritDoc
     */
    public function findByOpportunityId(string $opportunityId): ?array
    {
        $this->logger?->debug('Finding quotation by opportunity ID', [
            'opportunity_id' => $opportunityId,
        ]);

        // In real implementation, this would call the Sales package
        // return $this->salesService->findQuotationByOpportunityId($opportunityId);
        
        return null;
    }

    /**
     * @inheritDoc
     */
    public function createFromOpportunity(array $opportunityData): string
    {
        $this->logger?->info('Creating quotation from opportunity', [
            'opportunity_id' => $opportunityData['id'] ?? null,
        ]);

        // In real implementation, this would call the Sales package
        // $quotation = $this->salesService->createQuotation([
        //     'title' => $opportunityData['title'],
        //     'value' => $opportunityData['value'],
        //     'currency' => $opportunityData['currency'],
        //     ...
        // ]);
        // return $quotation->getId();

        return sprintf('QUO-%s', uniqid());
    }

    /**
     * @inheritDoc
     */
    public function update(string $quotationId, array $data): void
    {
        $this->logger?->info('Updating quotation', [
            'quotation_id' => $quotationId,
        ]);

        // In real implementation, this would call the Sales package
        // $this->salesService->updateQuotation($quotationId, $data);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(string $quotationId): string
    {
        $quotation = $this->findById($quotationId);
        return $quotation['status'] ?? 'draft';
    }

    /**
     * @inheritDoc
     */
    public function markAsSent(string $quotationId): void
    {
        $this->logger?->info('Marking quotation as sent', [
            'quotation_id' => $quotationId,
        ]);

        // In real implementation, this would update the quotation status
        // $this->salesService->updateQuotationStatus($quotationId, 'sent');
    }

    /**
     * @inheritDoc
     */
    public function markAsAccepted(string $quotationId): void
    {
        $this->logger?->info('Marking quotation as accepted', [
            'quotation_id' => $quotationId,
        ]);

        // In real implementation, this would update the quotation status
        // $this->salesService->updateQuotationStatus($quotationId, 'accepted');
    }

    /**
     * @inheritDoc
     */
    public function markAsRejected(string $quotationId, string $reason): void
    {
        $this->logger?->info('Marking quotation as rejected', [
            'quotation_id' => $quotationId,
            'reason' => $reason,
        ]);

        // In real implementation, this would update the quotation status
        // $this->salesService->updateQuotationStatus($quotationId, 'rejected', $reason);
    }

    /**
     * @inheritDoc
     */
    public function getTotalValue(string $quotationId): int
    {
        $quotation = $this->findById($quotationId);
        return $quotation['total_value'] ?? 0;
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(string $quotationId): string
    {
        $quotation = $this->findById($quotationId);
        return $quotation['currency'] ?? 'USD';
    }

    /**
     * @inheritDoc
     */
    public function isExpired(string $quotationId): bool
    {
        $expiryDate = $this->getExpiryDate($quotationId);
        return $expiryDate < new \DateTimeImmutable();
    }

    /**
     * @inheritDoc
     */
    public function getExpiryDate(string $quotationId): \DateTimeImmutable
    {
        $quotation = $this->findById($quotationId);
        
        if ($quotation === null) {
            return new \DateTimeImmutable();
        }

        return new \DateTimeImmutable($quotation['expiry_date'] ?? '+30 days');
    }
}