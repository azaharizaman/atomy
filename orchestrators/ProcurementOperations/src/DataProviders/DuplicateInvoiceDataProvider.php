<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\ProcurementOperations\Contracts\InvoiceDuplicateQueryInterface;
use Nexus\ProcurementOperations\DTOs\DuplicateCheckRequest;

/**
 * Data provider for duplicate invoice detection.
 *
 * Aggregates invoice data from query interface for duplicate checking.
 */
final readonly class DuplicateInvoiceDataProvider
{
    public function __construct(
        private InvoiceDuplicateQueryInterface $invoiceQuery,
    ) {}

    /**
     * Get all potential matches for a duplicate check request.
     *
     * @return array<string, array<array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string}>>
     */
    public function getAllPotentialMatches(DuplicateCheckRequest $request): array
    {
        $matches = [];

        // Check exact invoice number match
        $exactMatches = $this->invoiceQuery->findByExactInvoiceNumber(
            $request->tenantId,
            $request->vendorId,
            $request->invoiceNumber,
            $request->excludeInvoiceId
        );
        if (!empty($exactMatches)) {
            $matches['exact'] = $exactMatches;
        }

        // Check normalized invoice number match
        $normalizedNumber = $request->getNormalizedInvoiceNumber();
        $normalizedMatches = $this->invoiceQuery->findByNormalizedInvoiceNumber(
            $request->tenantId,
            $request->vendorId,
            $normalizedNumber,
            $request->excludeInvoiceId
        );
        // Filter out exact matches to avoid duplicates
        $normalizedMatches = array_filter(
            $normalizedMatches,
            fn(array $inv) => !isset($matches['exact']) || !$this->isInArray($inv['id'], $matches['exact'])
        );
        if (!empty($normalizedMatches)) {
            $matches['normalized'] = array_values($normalizedMatches);
        }

        // Check amount + date match
        $amountDateMatches = $this->invoiceQuery->findByAmountAndDate(
            $request->tenantId,
            $request->vendorId,
            $request->invoiceAmount->getAmount(),
            $request->invoiceAmount->getCurrency(),
            $request->invoiceDate,
            3, // 3 days tolerance
            $request->excludeInvoiceId
        );
        // Filter out already found matches
        $amountDateMatches = $this->filterAlreadyMatched($amountDateMatches, $matches);
        if (!empty($amountDateMatches)) {
            $matches['amount_date'] = $amountDateMatches;
        }

        // Check amount only match (broader search)
        $amountMatches = $this->invoiceQuery->findByAmount(
            $request->tenantId,
            $request->vendorId,
            $request->invoiceAmount->getAmount(),
            $request->invoiceAmount->getCurrency(),
            $request->getLookbackDate(),
            $request->excludeInvoiceId
        );
        // Filter out already found matches
        $amountMatches = $this->filterAlreadyMatched($amountMatches, $matches);
        if (!empty($amountMatches)) {
            $matches['amount_vendor'] = $amountMatches;
        }

        // Check PO reference match
        if ($request->poNumber !== null) {
            $poMatches = $this->invoiceQuery->findByPOReference(
                $request->tenantId,
                $request->poNumber,
                $request->excludeInvoiceId
            );
            $poMatches = $this->filterAlreadyMatched($poMatches, $matches);
            if (!empty($poMatches)) {
                $matches['po_reference'] = $poMatches;
            }
        }

        // Check document hash match
        if ($request->documentHash !== null) {
            $hashMatches = $this->invoiceQuery->findByDocumentHash(
                $request->tenantId,
                $request->documentHash,
                $request->excludeInvoiceId
            );
            $hashMatches = $this->filterAlreadyMatched($hashMatches, $matches);
            if (!empty($hashMatches)) {
                $matches['hash'] = $hashMatches;
            }
        }

        // Check fingerprint match
        $fingerprint = $request->generateFingerprint();
        $fingerprintMatches = $this->invoiceQuery->findByFingerprint(
            $request->tenantId,
            $fingerprint,
            $request->excludeInvoiceId
        );
        $fingerprintMatches = $this->filterAlreadyMatched($fingerprintMatches, $matches);
        if (!empty($fingerprintMatches)) {
            $matches['fingerprint'] = $fingerprintMatches;
        }

        return $matches;
    }

    /**
     * Check if invoice ID is in array.
     *
     * @param array<array{id: string}> $invoices
     */
    private function isInArray(string $id, array $invoices): bool
    {
        foreach ($invoices as $invoice) {
            if ($invoice['id'] === $id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Filter out invoices that are already in other match categories.
     *
     * @param array<array{id: string}> $invoices
     * @param array<string, array<array{id: string}>> $existingMatches
     * @return array<array{id: string}>
     */
    private function filterAlreadyMatched(array $invoices, array $existingMatches): array
    {
        $existingIds = [];
        foreach ($existingMatches as $category => $matches) {
            foreach ($matches as $match) {
                $existingIds[$match['id']] = true;
            }
        }

        return array_values(array_filter(
            $invoices,
            fn(array $inv) => !isset($existingIds[$inv['id']])
        ));
    }
}
