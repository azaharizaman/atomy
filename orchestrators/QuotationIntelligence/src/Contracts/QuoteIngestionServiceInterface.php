<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Interface for ingestion of unstructured vendor quote files.
 */
interface QuoteIngestionServiceInterface
{
    /**
     * Upload and validate a vendor quote document.
     * 
     * @param string $tenantId
     * @param string $rfqId Associated RFQ
     * @param string $vendorId The vendor who submitted the quote
     * @param string $tempFilePath Path to the uploaded file
     * @param string $originalFilename The user-provided file name
     * 
     * @return string The unique Document ID (ULID) in Nexus\Storage
     * 
     * @throws \Nexus\QuotationIntelligence\Exceptions\InvalidQuoteFileException If format/size invalid
     */
    public function ingest(
        string $tenantId,
        string $rfqId,
        string $vendorId,
        string $tempFilePath,
        string $originalFilename
    ): string;

    /**
     * Validate if the file is a supported quote format (PDF/XLS).
     */
    public function isValidFormat(string $mimeType): bool;
}
