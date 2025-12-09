<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\Common\ValueObjects\Money;

/**
 * Request for duplicate invoice detection.
 *
 * Contains all the invoice data needed to check for duplicates
 * against existing invoices in the system.
 */
final readonly class DuplicateCheckRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string $invoiceNumber Vendor's invoice number
     * @param Money $invoiceAmount Total invoice amount
     * @param \DateTimeImmutable $invoiceDate Invoice date
     * @param string|null $poNumber Associated PO number (if any)
     * @param string|null $documentHash Hash of invoice document (for content matching)
     * @param string|null $excludeInvoiceId Invoice ID to exclude (for updates)
     * @param int $lookbackDays Days to look back for duplicates (default 365)
     * @param bool $strictMode If true, any match blocks processing
     */
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public string $invoiceNumber,
        public Money $invoiceAmount,
        public \DateTimeImmutable $invoiceDate,
        public ?string $poNumber = null,
        public ?string $documentHash = null,
        public ?string $excludeInvoiceId = null,
        public int $lookbackDays = 365,
        public bool $strictMode = false,
    ) {}

    /**
     * Create normalized invoice number for comparison.
     *
     * Removes common variations: spaces, dashes, leading zeros.
     */
    public function getNormalizedInvoiceNumber(): string
    {
        // Remove spaces, dashes, underscores
        $normalized = preg_replace('/[\s\-_]+/', '', $this->invoiceNumber);

        // Convert to uppercase
        $normalized = strtoupper($normalized ?? $this->invoiceNumber);

        // Remove leading zeros in numeric segments
        $normalized = preg_replace('/(?<=^|[A-Z])0+(?=\d)/', '', $normalized);

        return $normalized ?? $this->invoiceNumber;
    }

    /**
     * Generate a fingerprint hash for quick duplicate detection.
     *
     * Based on: vendor + normalized invoice number + amount (rounded)
     */
    public function generateFingerprint(): string
    {
        $data = implode('|', [
            $this->tenantId,
            $this->vendorId,
            $this->getNormalizedInvoiceNumber(),
            $this->invoiceAmount->getCurrency(),
            (string) round($this->invoiceAmount->getAmount(), 2),
        ]);

        return hash('sha256', $data);
    }

    /**
     * Get the lookback date for duplicate search.
     */
    public function getLookbackDate(): \DateTimeImmutable
    {
        return $this->invoiceDate->modify("-{$this->lookbackDays} days");
    }
}
