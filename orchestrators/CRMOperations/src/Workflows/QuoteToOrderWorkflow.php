<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Workflows;

use Nexus\CRMOperations\Contracts\QuotationProviderInterface;
use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Nexus\CRMOperations\Exceptions\QuoteOperationException;
use Psr\Log\LoggerInterface;

final readonly class QuoteToOrderWorkflow
{
    public function __construct(
        private QuotationProviderInterface $quotationProvider,
        private NotificationProviderInterface $notificationProvider,
        private ?LoggerInterface $logger = null
    ) {}

    public function generateQuote(string $opportunityId, array $lineItems): string
    {
        $quoteId = $this->quotationProvider->createFromOpportunity([
            'opportunity_id' => $opportunityId,
            'line_items' => $lineItems
        ]);
        
        $this->logger?->info('Quote generated from opportunity', [
            'quote_id' => $quoteId,
            'opportunity_id' => $opportunityId
        ]);
        
        return $quoteId;
    }

    public function submitForApproval(string $quoteId): void
    {
        $this->quotationProvider->markAsSent($quoteId);
        
        $this->notificationProvider->notify(
            'sales_team',
            'Quote submitted for approval',
            sprintf('Quote %s needs approval', $quoteId),
            ['quote_id' => $quoteId]
        );
        
        $this->logger?->info('Quote submitted for approval', ['quote_id' => $quoteId]);
    }

    public function trackQuoteStatus(string $quoteId): string
    {
        return $this->quotationProvider->getStatus($quoteId);
    }

    public function convertToOrder(string $quoteId): string
    {
        $quote = $this->quotationProvider->findById($quoteId);
        
        if ($quote === null) {
            throw QuoteOperationException::notFound($quoteId);
        }
        
        $status = $quote['status'] ?? '';
        
        if ($status !== 'accepted') {
            throw QuoteOperationException::invalidStatus($quoteId, $status);
        }
        
        $orderId = uniqid('order_');
        
        $this->logger?->info('Quote converted to order', [
            'quote_id' => $quoteId,
            'order_id' => $orderId
        ]);
        
        return $orderId;
    }

    public function handleExpiration(string $quoteId): void
    {
        $quote = $this->quotationProvider->findById($quoteId);
        
        if ($quote === null) {
            return;
        }
        
        $expiryDate = $this->quotationProvider->getExpiryDate($quoteId);
        
        if ($expiryDate !== null && $expiryDate < new \DateTimeImmutable()) {
            $this->notificationProvider->notify(
                'sales_team',
                'Quote expired',
                sprintf('Quote %s has expired', $quoteId),
                ['quote_id' => $quoteId]
            );
            
            $this->logger?->info('Quote expired', ['quote_id' => $quoteId]);
        }
    }
}
