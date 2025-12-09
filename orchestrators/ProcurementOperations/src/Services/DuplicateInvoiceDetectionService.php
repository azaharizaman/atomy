<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DataProviders\DuplicateInvoiceDataProvider;
use Nexus\ProcurementOperations\DTOs\DuplicateCheckRequest;
use Nexus\ProcurementOperations\DTOs\DuplicateCheckResult;
use Nexus\ProcurementOperations\Enums\DuplicateMatchType;
use Nexus\ProcurementOperations\Events\DuplicateInvoiceDetectedEvent;
use Nexus\ProcurementOperations\Events\DuplicateCheckPassedEvent;
use Nexus\ProcurementOperations\ValueObjects\DuplicateMatch;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for detecting duplicate invoices.
 *
 * Implements multiple detection strategies to identify potential
 * duplicate invoices before payment processing.
 */
final readonly class DuplicateInvoiceDetectionService
{
    public function __construct(
        private DuplicateInvoiceDataProvider $dataProvider,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Check for duplicate invoices.
     */
    public function checkForDuplicates(DuplicateCheckRequest $request): DuplicateCheckResult
    {
        $this->logger->info('Starting duplicate invoice check', [
            'tenant_id' => $request->tenantId,
            'vendor_id' => $request->vendorId,
            'invoice_number' => $request->invoiceNumber,
            'amount' => $request->invoiceAmount->getAmount(),
            'currency' => $request->invoiceAmount->getCurrency(),
        ]);

        // Get all potential matches from data provider
        $potentialMatches = $this->dataProvider->getAllPotentialMatches($request);

        // Convert to DuplicateMatch objects
        $matches = $this->processMatches($potentialMatches, $request);

        // Create result
        $fingerprint = $request->generateFingerprint();
        $result = DuplicateCheckResult::fromMatches($matches, $fingerprint, $request->strictMode);

        // Dispatch appropriate event
        if ($result->hasDuplicates) {
            $this->eventDispatcher->dispatch(new DuplicateInvoiceDetectedEvent(
                tenantId: $request->tenantId,
                vendorId: $request->vendorId,
                invoiceNumber: $request->invoiceNumber,
                amount: $request->invoiceAmount,
                invoiceDate: $request->invoiceDate,
                matchCount: count($matches),
                shouldBlock: $result->shouldBlock,
                highestRiskLevel: $result->highestRiskLevel,
                matches: array_map(fn(DuplicateMatch $m) => $m->toArray(), $matches),
            ));

            $this->logger->warning('Duplicate invoices detected', [
                'match_count' => count($matches),
                'should_block' => $result->shouldBlock,
                'highest_risk' => $result->highestRiskLevel,
            ]);
        } else {
            $this->eventDispatcher->dispatch(new DuplicateCheckPassedEvent(
                tenantId: $request->tenantId,
                vendorId: $request->vendorId,
                invoiceNumber: $request->invoiceNumber,
                fingerprint: $fingerprint,
            ));

            $this->logger->info('No duplicate invoices detected');
        }

        return $result;
    }

    /**
     * Process raw matches into DuplicateMatch objects.
     *
     * @param array<string, array<array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string}>> $potentialMatches
     * @return array<DuplicateMatch>
     */
    private function processMatches(array $potentialMatches, DuplicateCheckRequest $request): array
    {
        $matches = [];

        // Process exact matches
        if (isset($potentialMatches['exact'])) {
            foreach ($potentialMatches['exact'] as $inv) {
                // Check if amount also matches for true exact match
                $matchType = abs($inv['amount'] - $request->invoiceAmount->getAmount()) < 0.01
                    ? DuplicateMatchType::EXACT_MATCH
                    : DuplicateMatchType::INVOICE_NUMBER_MATCH;

                $matches[] = $this->createMatch($inv, $matchType);
            }
        }

        // Process normalized matches (fuzzy invoice number)
        if (isset($potentialMatches['normalized'])) {
            foreach ($potentialMatches['normalized'] as $inv) {
                $matches[] = $this->createMatch($inv, DuplicateMatchType::FUZZY_INVOICE_NUMBER, [
                    'original_number' => $inv['invoice_number'],
                    'searched_number' => $request->invoiceNumber,
                ]);
            }
        }

        // Process amount + date matches
        if (isset($potentialMatches['amount_date'])) {
            foreach ($potentialMatches['amount_date'] as $inv) {
                $matches[] = $this->createMatch($inv, DuplicateMatchType::AMOUNT_DATE_MATCH);
            }
        }

        // Process amount only matches
        if (isset($potentialMatches['amount_vendor'])) {
            foreach ($potentialMatches['amount_vendor'] as $inv) {
                $matches[] = $this->createMatch($inv, DuplicateMatchType::AMOUNT_VENDOR_MATCH);
            }
        }

        // Process PO reference matches
        if (isset($potentialMatches['po_reference'])) {
            foreach ($potentialMatches['po_reference'] as $inv) {
                $matches[] = $this->createMatch($inv, DuplicateMatchType::PO_REFERENCE_MATCH, [
                    'po_number' => $inv['po_number'] ?? $request->poNumber,
                ]);
            }
        }

        // Process hash matches
        if (isset($potentialMatches['hash'])) {
            foreach ($potentialMatches['hash'] as $inv) {
                $matches[] = $this->createMatch($inv, DuplicateMatchType::HASH_COLLISION);
            }
        }

        // Process fingerprint matches
        if (isset($potentialMatches['fingerprint'])) {
            foreach ($potentialMatches['fingerprint'] as $inv) {
                // Fingerprint match that's not already in exact matches
                $matches[] = $this->createMatch($inv, DuplicateMatchType::HASH_COLLISION, [
                    'match_source' => 'fingerprint',
                ]);
            }
        }

        // Sort by confidence (highest first)
        usort($matches, fn(DuplicateMatch $a, DuplicateMatch $b) =>
            $b->confidenceScore <=> $a->confidenceScore
        );

        return $matches;
    }

    /**
     * Create a DuplicateMatch from invoice data.
     *
     * @param array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string} $invoice
     * @param array<string, mixed> $details
     */
    private function createMatch(
        array $invoice,
        DuplicateMatchType $matchType,
        array $details = []
    ): DuplicateMatch {
        return new DuplicateMatch(
            matchedInvoiceId: $invoice['id'],
            matchedInvoiceNumber: $invoice['invoice_number'],
            matchType: $matchType,
            confidenceScore: $matchType->getConfidenceLevel(),
            matchedAmount: Money::of($invoice['amount'], $invoice['currency']),
            matchedDate: new \DateTimeImmutable($invoice['date']),
            matchedStatus: $invoice['status'],
            matchDetails: $details,
        );
    }
}
