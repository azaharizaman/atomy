<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Contracts\VendorQuoteInterface;
use Nexus\Procurement\Contracts\VendorQuoteRepositoryInterface;
use Nexus\Procurement\Exceptions\QuoteLockedException;
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
     * @param string $tenantId
     * @param string $quoteId
     * @param string $acceptorId
     * @return VendorQuoteInterface
     * @throws QuoteLockedException
     */
    public function acceptQuote(string $tenantId, string $quoteId, string $acceptorId): VendorQuoteInterface
    {
        $quote = $this->repository->findById($tenantId, $quoteId);

        if ($quote === null) {
            throw new \InvalidArgumentException("Vendor quote with ID '{$quoteId}' not found.");
        }

        $this->guardAgainstLock($quote);

        $this->logger->info('Accepting vendor quote', [
            'tenant_id' => $tenantId,
            'quote_id' => $quoteId,
            'rfq_number' => $quote->getRfqNumber(),
            'acceptor_id' => $acceptorId,
        ]);

        $acceptedQuote = $this->repository->accept($tenantId, $quoteId, $acceptorId);

        $this->logger->info('Vendor quote accepted', [
            'tenant_id' => $tenantId,
            'quote_id' => $quoteId,
            'rfq_number' => $acceptedQuote->getRfqNumber(),
            'status' => $acceptedQuote->getStatus(),
        ]);

        return $acceptedQuote;
    }

    /**
     * Reject vendor quote.
     *
     * @param string $tenantId
     * @param string $quoteId
     * @param string $reason
     * @return VendorQuoteInterface
     * @throws QuoteLockedException
     */
    public function rejectQuote(string $tenantId, string $quoteId, string $reason): VendorQuoteInterface
    {
        $quote = $this->repository->findById($tenantId, $quoteId);

        if ($quote === null) {
            throw new \InvalidArgumentException("Vendor quote with ID '{$quoteId}' not found.");
        }

        $this->guardAgainstLock($quote);

        $this->logger->info('Rejecting vendor quote', [
            'tenant_id' => $tenantId,
            'quote_id' => $quoteId,
            'rfq_number' => $quote->getRfqNumber(),
            'reason' => $reason,
        ]);

        $rejectedQuote = $this->repository->reject($tenantId, $quoteId, $reason);

        return $rejectedQuote;
    }

    /**
     * Lock a quote for an active comparison run, preventing mutations.
     *
     * @throws QuoteLockedException If already locked by a different run.
     */
    public function lockQuote(string $tenantId, string $quoteId, string $comparisonRunId, string $lockedBy): VendorQuoteInterface
    {
        $quote = $this->repository->findById($tenantId, $quoteId);

        if ($quote === null) {
            throw new \InvalidArgumentException("Vendor quote with ID '{$quoteId}' not found.");
        }

        if ($quote->isLocked() && $quote->getLockedByRunId() !== $comparisonRunId) {
            throw QuoteLockedException::alreadyLocked($quoteId, (string) $quote->getLockedByRunId());
        }

        $this->logger->info('Locking vendor quote for comparison run', [
            'tenant_id' => $tenantId,
            'quote_id' => $quoteId,
            'comparison_run_id' => $comparisonRunId,
            'locked_by' => $lockedBy,
        ]);

        return $this->repository->lock($tenantId, $quoteId, $comparisonRunId, $lockedBy);
    }

    /**
     * Unlock a quote when a comparison run completes or is discarded.
     *
     * @throws QuoteLockedException If the run ID does not match the current lock holder.
     */
    public function unlockQuote(string $tenantId, string $quoteId, string $comparisonRunId): VendorQuoteInterface
    {
        $quote = $this->repository->findById($tenantId, $quoteId);

        if ($quote === null) {
            throw new \InvalidArgumentException("Vendor quote with ID '{$quoteId}' not found.");
        }

        if ($quote->isLocked() && $quote->getLockedByRunId() !== $comparisonRunId) {
            throw QuoteLockedException::lockMismatch($quoteId, $comparisonRunId, $quote->getLockedByRunId());
        }

        $this->logger->info('Unlocking vendor quote from comparison run', [
            'tenant_id' => $tenantId,
            'quote_id' => $quoteId,
            'comparison_run_id' => $comparisonRunId,
        ]);

        return $this->repository->unlock($tenantId, $quoteId, $comparisonRunId);
    }

    /**
     * Release all locks held by a specific comparison run (batch unlock).
     *
     * @return int Number of quotes unlocked.
     */
    public function unlockAllForRun(string $tenantId, string $comparisonRunId): int
    {
        $lockedQuotes = $this->repository->findLockedByRun($tenantId, $comparisonRunId);
        $count = count($lockedQuotes);

        foreach ($lockedQuotes as $quote) {
            $this->repository->unlock($tenantId, $quote->getId(), $comparisonRunId);
        }

        if ($count > 0) {
            $this->logger->info('Batch-unlocked quotes for comparison run', [
                'tenant_id' => $tenantId,
                'comparison_run_id' => $comparisonRunId,
                'unlocked_count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Get vendor quote by ID.
     *
     * @param string $tenantId
     * @param string $quoteId
     * @return VendorQuoteInterface|null
     */
    public function getQuote(string $tenantId, string $quoteId): ?VendorQuoteInterface
    {
        return $this->repository->findById($tenantId, $quoteId);
    }

    /**
     * Get all quotes for requisition.
     *
     * @param string $tenantId
     * @param string $requisitionId
     * @return array<VendorQuoteInterface>
     */
    public function getQuotesForRequisition(string $tenantId, string $requisitionId): array
    {
        return $this->repository->findByRequisitionId($tenantId, $requisitionId);
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
     * @throws QuoteLockedException
     */
    private function guardAgainstLock(VendorQuoteInterface $quote): void
    {
        if ($quote->isLocked()) {
            throw QuoteLockedException::cannotModify($quote->getId(), (string) $quote->getLockedByRunId());
        }
    }

    /**
     * Compare quotes for a requisition.
     *
     * Returns comparison matrix for vendor selection.
     *
     * @param string $tenantId
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
    public function compareQuotes(string $tenantId, string $requisitionId): array
    {
        $quotes = $this->repository->findByRequisitionId($tenantId, $requisitionId);

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
            'tenant_id' => $tenantId,
            'requisition_id' => $requisitionId,
            'quote_count' => count($quotes),
            'recommended_quote_id' => $lowestQuoteId,
        ]);

        return $comparison;
    }
}
