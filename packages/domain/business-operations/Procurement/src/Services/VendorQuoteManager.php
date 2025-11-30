<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Contracts\VendorQuoteInterface;
use Nexus\Procurement\Contracts\VendorQuoteRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages vendor quotes and RFQ (Request for Quotation) process.
 */
final readonly class VendorQuoteManager
{
    public function __construct(
        private VendorQuoteRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create vendor quote from RFQ.
     *
     * @param string $tenantId
     * @param string $requisitionId Associated requisition
     * @param array{
     *   rfq_number: string,
     *   vendor_id: string,
     *   quote_reference: string,
     *   quoted_date: string,
     *   valid_until: string,
     *   lines: array<array{item_code: string, description: string, quantity: float, unit: string, unit_price: float, lead_time_days?: int}>,
     *   payment_terms?: string,
     *   delivery_terms?: string,
     *   notes?: string,
     *   metadata?: array
     * } $data
     * @return VendorQuoteInterface
     */
    public function createQuote(string $tenantId, string $requisitionId, array $data): VendorQuoteInterface
    {
        $this->logger->info('Creating vendor quote', [
            'tenant_id' => $tenantId,
            'requisition_id' => $requisitionId,
            'rfq_number' => $data['rfq_number'],
            'vendor_id' => $data['vendor_id'],
            'quote_reference' => $data['quote_reference'],
        ]);

        $quote = $this->repository->create($tenantId, $requisitionId, $data);

        $this->logger->info('Vendor quote created', [
            'tenant_id' => $tenantId,
            'quote_id' => $quote->getId(),
            'rfq_number' => $quote->getRfqNumber(),
            'status' => $quote->getStatus(),
        ]);

        return $quote;
    }

    /**
     * Accept vendor quote.
     *
     * @param string $quoteId
     * @param string $acceptorId
     * @return VendorQuoteInterface
     */
    public function acceptQuote(string $quoteId, string $acceptorId): VendorQuoteInterface
    {
        $quote = $this->repository->findById($quoteId);

        if ($quote === null) {
            throw new \InvalidArgumentException("Vendor quote with ID '{$quoteId}' not found.");
        }

        $this->logger->info('Accepting vendor quote', [
            'quote_id' => $quoteId,
            'rfq_number' => $quote->getRfqNumber(),
            'acceptor_id' => $acceptorId,
        ]);

        $acceptedQuote = $this->repository->accept($quoteId, $acceptorId);

        $this->logger->info('Vendor quote accepted', [
            'quote_id' => $quoteId,
            'rfq_number' => $acceptedQuote->getRfqNumber(),
            'status' => $acceptedQuote->getStatus(),
        ]);

        return $acceptedQuote;
    }

    /**
     * Reject vendor quote.
     *
     * @param string $quoteId
     * @param string $reason
     * @return VendorQuoteInterface
     */
    public function rejectQuote(string $quoteId, string $reason): VendorQuoteInterface
    {
        $quote = $this->repository->findById($quoteId);

        if ($quote === null) {
            throw new \InvalidArgumentException("Vendor quote with ID '{$quoteId}' not found.");
        }

        $this->logger->info('Rejecting vendor quote', [
            'quote_id' => $quoteId,
            'rfq_number' => $quote->getRfqNumber(),
            'reason' => $reason,
        ]);

        $rejectedQuote = $this->repository->reject($quoteId, $reason);

        return $rejectedQuote;
    }

    /**
     * Get vendor quote by ID.
     *
     * @param string $quoteId
     * @return VendorQuoteInterface|null
     */
    public function getQuote(string $quoteId): ?VendorQuoteInterface
    {
        return $this->repository->findById($quoteId);
    }

    /**
     * Get all quotes for requisition.
     *
     * @param string $requisitionId
     * @return array<VendorQuoteInterface>
     */
    public function getQuotesForRequisition(string $requisitionId): array
    {
        return $this->repository->findByRequisitionId($requisitionId);
    }

    /**
     * Get quotes by vendor.
     *
     * @param string $tenantId
     * @param string $vendorId
     * @return array<VendorQuoteInterface>
     */
    public function getQuotesByVendor(string $tenantId, string $vendorId): array
    {
        return $this->repository->findByVendorId($tenantId, $vendorId);
    }

    /**
     * Compare quotes for a requisition.
     *
     * Returns comparison matrix for vendor selection.
     *
     * @param string $requisitionId
     * @return array{
     *   requisition_id: string,
     *   quote_count: int,
     *   quotes: array<array{
     *     quote_id: string,
     *     vendor_id: string,
     *     total_quoted: float,
     *     average_lead_time_days: int,
     *     payment_terms: string|null,
     *     status: string
     *   }>,
     *   recommendation: array{quote_id: string, reason: string}|null
     * }
     */
    public function compareQuotes(string $requisitionId): array
    {
        $quotes = $this->repository->findByRequisitionId($requisitionId);

        $comparison = [
            'requisition_id' => $requisitionId,
            'quote_count' => count($quotes),
            'quotes' => [],
            'recommendation' => null,
        ];

        $lowestTotal = PHP_FLOAT_MAX;
        $lowestQuoteId = null;

        foreach ($quotes as $quote) {
            $lines = $quote->getLines();
            $totalQuoted = 0.0;
            $totalLeadTime = 0;
            $lineCount = count($lines);

            foreach ($lines as $line) {
                $totalQuoted += ($line['quantity'] * $line['unit_price']);
                $totalLeadTime += $line['lead_time_days'] ?? 0;
            }

            $avgLeadTime = $lineCount > 0 ? (int)($totalLeadTime / $lineCount) : 0;

            $comparison['quotes'][] = [
                'quote_id' => $quote->getId(),
                'vendor_id' => $quote->getVendorId(),
                'total_quoted' => $totalQuoted,
                'average_lead_time_days' => $avgLeadTime,
                'payment_terms' => $quote->getPaymentTerms(),
                'status' => $quote->getStatus(),
            ];

            if ($totalQuoted < $lowestTotal && $quote->getStatus() === 'pending') {
                $lowestTotal = $totalQuoted;
                $lowestQuoteId = $quote->getId();
            }
        }

        if ($lowestQuoteId !== null) {
            $comparison['recommendation'] = [
                'quote_id' => $lowestQuoteId,
                'reason' => 'Lowest total quoted price among pending quotes.',
            ];
        }

        $this->logger->info('Quote comparison generated', [
            'requisition_id' => $requisitionId,
            'quote_count' => count($quotes),
            'recommended_quote_id' => $lowestQuoteId,
        ]);

        return $comparison;
    }
}
