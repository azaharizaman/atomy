<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Sales\Contracts\QuotationInterface;
use Nexus\Sales\Contracts\QuotationRepositoryInterface;
use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Enums\QuoteStatus;
use Nexus\Sales\Exceptions\InvalidQuoteStatusException;
use Nexus\Sales\Exceptions\QuotationNotFoundException;
use Nexus\Sequencing\Contracts\SequenceGeneratorInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for converting quotations to sales orders.
 */
final readonly class QuoteToOrderConverter
{
    public function __construct(
        private QuotationRepositoryInterface $quotationRepository,
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private SequenceGeneratorInterface $sequenceGenerator,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    /**
     * Convert accepted quotation to sales order.
     *
     * @param string $quotationId
     * @param array $orderData Additional order-specific data (payment terms, shipping, etc.)
     * @return SalesOrderInterface
     * @throws QuotationNotFoundException
     * @throws InvalidQuoteStatusException
     */
    public function convertToOrder(string $quotationId, array $orderData = []): SalesOrderInterface
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        if (!$quotation->getStatus()->canBeConverted()) {
            throw InvalidQuoteStatusException::cannotConvert($quotationId, $quotation->getStatus());
        }

        // Generate order number
        $orderNumber = $this->sequenceGenerator->generate(
            $quotation->getTenantId(),
            'sales_order',
            ['prefix' => 'SO']
        );

        // Create sales order from quotation
        // (Implementation-specific entity creation will be in Atomy)
        // Key mappings:
        // - Copy all line items with same quantities, prices
        // - Copy discount rules
        // - Copy currency (exchange rate will be locked on confirmation)
        // - Copy customer, totals
        // - Set order status to DRAFT
        // - Link to source quotation via converted_to_order_id

        $this->logger->info('Converting quotation to order', [
            'quotation_id' => $quotationId,
            'quote_number' => $quotation->getQuoteNumber(),
            'order_number' => $orderNumber,
        ]);

        // Update quotation status to CONVERTED_TO_ORDER
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $quotationId,
            'quotation_converted',
            "Quotation {$quotation->getQuoteNumber()} converted to order {$orderNumber}"
        );

        // This is a placeholder - actual implementation will be in Atomy
        throw new \RuntimeException('Quote-to-order conversion not implemented in package layer');
    }

    /**
     * Validate if quotation can be converted.
     *
     * @param string $quotationId
     * @return bool
     * @throws QuotationNotFoundException
     */
    public function canConvertToOrder(string $quotationId): bool
    {
        $quotation = $this->quotationRepository->findById($quotationId);
        
        return $quotation->getStatus()->canBeConverted() 
            && $quotation->getConvertedToOrderId() === null;
    }
}
