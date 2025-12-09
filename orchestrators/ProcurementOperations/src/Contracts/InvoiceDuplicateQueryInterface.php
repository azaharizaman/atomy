<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\DuplicateCheckRequest;

/**
 * Interface for querying existing invoices for duplicate detection.
 *
 * Consuming applications must implement this interface to provide
 * access to existing invoice data for duplicate checking.
 */
interface InvoiceDuplicateQueryInterface
{
    /**
     * Find invoices with exact invoice number match.
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string $invoiceNumber Invoice number to search for
     * @param string|null $excludeId Invoice ID to exclude from results
     * @return array<array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string}>
     */
    public function findByExactInvoiceNumber(
        string $tenantId,
        string $vendorId,
        string $invoiceNumber,
        ?string $excludeId = null
    ): array;

    /**
     * Find invoices with normalized invoice number match.
     *
     * Should search using normalized comparison (no spaces, dashes, uppercase).
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string $normalizedNumber Normalized invoice number
     * @param string|null $excludeId Invoice ID to exclude from results
     * @return array<array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string}>
     */
    public function findByNormalizedInvoiceNumber(
        string $tenantId,
        string $vendorId,
        string $normalizedNumber,
        ?string $excludeId = null
    ): array;

    /**
     * Find invoices with matching amount and date from same vendor.
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param float $amount Invoice amount
     * @param string $currency Currency code
     * @param \DateTimeImmutable $date Invoice date
     * @param int $dateTolerance Days tolerance for date matching (default 3)
     * @param string|null $excludeId Invoice ID to exclude from results
     * @return array<array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string}>
     */
    public function findByAmountAndDate(
        string $tenantId,
        string $vendorId,
        float $amount,
        string $currency,
        \DateTimeImmutable $date,
        int $dateTolerance = 3,
        ?string $excludeId = null
    ): array;

    /**
     * Find invoices with matching amount from same vendor.
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param float $amount Invoice amount
     * @param string $currency Currency code
     * @param \DateTimeImmutable $since Only search invoices since this date
     * @param string|null $excludeId Invoice ID to exclude from results
     * @return array<array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string}>
     */
    public function findByAmount(
        string $tenantId,
        string $vendorId,
        float $amount,
        string $currency,
        \DateTimeImmutable $since,
        ?string $excludeId = null
    ): array;

    /**
     * Find invoices referencing the same PO.
     *
     * @param string $tenantId Tenant identifier
     * @param string $poNumber PO number
     * @param string|null $excludeId Invoice ID to exclude from results
     * @return array<array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string, po_number: string}>
     */
    public function findByPOReference(
        string $tenantId,
        string $poNumber,
        ?string $excludeId = null
    ): array;

    /**
     * Find invoices with matching document hash.
     *
     * @param string $tenantId Tenant identifier
     * @param string $documentHash SHA-256 hash of document content
     * @param string|null $excludeId Invoice ID to exclude from results
     * @return array<array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string}>
     */
    public function findByDocumentHash(
        string $tenantId,
        string $documentHash,
        ?string $excludeId = null
    ): array;

    /**
     * Find invoices with matching fingerprint.
     *
     * @param string $tenantId Tenant identifier
     * @param string $fingerprint Invoice fingerprint hash
     * @param string|null $excludeId Invoice ID to exclude from results
     * @return array<array{id: string, invoice_number: string, amount: float, currency: string, date: string, status: string}>
     */
    public function findByFingerprint(
        string $tenantId,
        string $fingerprint,
        ?string $excludeId = null
    ): array;
}
